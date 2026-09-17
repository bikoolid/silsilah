<script setup>
import * as d3 from 'd3';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    links: { type: Array, default: () => [] },
    selectedId: { type: [Number, String], default: null },
    query: { type: String, default: '' },
});

const emit = defineEmits(['select']);
const canvas = ref(null);
let svg;
let zoom;

function render() {
    if (!canvas.value) return;

    const width = Math.max(canvas.value.clientWidth, 900);
    const height = Math.max(canvas.value.clientHeight, 620);
    d3.select(canvas.value).selectAll('*').remove();
    svg = d3.select(canvas.value).append('svg').attr('width', '100%').attr('height', '100%').attr('viewBox', `0 0 ${width} ${height}`).attr('role', 'img').attr('aria-label', 'Pohon keluarga');
    const viewport = svg.append('g');

    zoom = d3.zoom().scaleExtent([0.35, 2.5]).on('zoom', (event) => viewport.attr('transform', event.transform));
    svg.call(zoom);

    const root = props.nodes.find((node) => node.isRoot) || props.nodes[0];
    const generation = new Map([[String(root?.id), 0]]);
    let generationQueue = [String(root?.id)];
    while (generationQueue.length) {
        const parentId = generationQueue.shift();
        props.links
            .filter((link) => link.type === 'parent-child' && String(link.source) === parentId)
            .forEach((link) => {
                const childId = String(link.target);
                if (!generation.has(childId)) {
                    generation.set(childId, (generation.get(parentId) || 0) + 1);
                    generationQueue.push(childId);
                }
            });
    }
    const rowFor = (node) => {
        if (node.isRoot) return 2;
        const nodeGeneration = generation.get(String(node.id));
        if (nodeGeneration !== undefined) return nodeGeneration + 2;
        const rootSpouse = props.links.some((link) => link.type === 'spouse'
            && (String(link.source) === String(root?.id) || String(link.target) === String(root?.id))
            && (String(link.source) === String(node.id) || String(link.target) === String(node.id)));
        if (rootSpouse) return 2;
        return 1;
    };
    const positioned = new Map();
    const rows = d3.group(props.nodes.map((node) => ({ node, row: rowFor(node) })), (item) => item.row);
    const placeCentered = (items, y) => {
        items.forEach((item, index) => {
            positioned.set(String(item.node.id), {
                ...item.node,
                x: width / 2 + (index - (items.length - 1) / 2) * 210,
                y,
            });
        });
    };
    placeCentered(rows.get(1) || [], 105);
    const rootNode = props.nodes.find((node) => node.isRoot);
    const rootSpouses = (rows.get(2) || []).filter((item) => !item.node.isRoot);
    const childrenByParent = d3.group(
        props.links.filter((link) => link.type === 'parent-child'),
        (link) => String(link.source),
    );
    const descendantPositions = new Map();
    let leafCursor = 0;
    const assignDescendantPosition = (nodeId, depth) => {
        const children = (childrenByParent.get(String(nodeId)) || [])
            .map((link) => props.nodes.find((node) => String(node.id) === String(link.target)))
            .filter(Boolean)
            .sort((a, b) => (a.birthYear || 9999) - (b.birthYear || 9999) || a.name.localeCompare(b.name));
        if (!children.length) {
            const x = leafCursor * 210;
            leafCursor += 1;
            descendantPositions.set(String(nodeId), { x, depth });
            return x;
        }
        const childX = children.map((child) => assignDescendantPosition(child.id, depth + 1));
        const x = (childX[0] + childX[childX.length - 1]) / 2;
        descendantPositions.set(String(nodeId), { x, depth });
        return x;
    };
    if (rootNode) assignDescendantPosition(rootNode.id, 0);
    if (rootNode) {
        const rootPosition = descendantPositions.get(String(rootNode.id));
        const shift = width / 2 - (rootPosition?.x || 0);
        props.nodes.forEach((node) => {
            const placement = descendantPositions.get(String(node.id));
            if (placement && placement.depth > 0) {
                positioned.set(String(node.id), {
                    ...node,
                    x: placement.x + shift,
                    y: 250 + placement.depth * 145,
                });
            }
        });
        positioned.set(String(rootNode.id), { ...rootNode, x: width / 2, y: 250 });
        rootSpouses.forEach((item, index) => {
            positioned.set(String(item.node.id), { ...item.node, x: width / 2 + (index + 1) * 210, y: 250 });
        });
    } else {
        placeCentered(rows.get(2) || [], 250);
    }
    const nodes = props.nodes.map((node) => positioned.get(String(node.id))).filter(Boolean);
    const byId = new Map(nodes.map((node) => [String(node.id), node]));
    const links = props.links.map((link) => ({ ...link, sourceNode: byId.get(String(link.source)), targetNode: byId.get(String(link.target)) })).filter((link) => link.sourceNode && link.targetNode);
    const match = props.query.trim().toLowerCase();

    const nodeHalfWidth = 92;
    const nodeHalfHeight = 48;
    const linkPath = (link) => {
        const source = link.sourceNode;
        const target = link.targetNode;

        if (link.type === 'spouse') {
            const direction = target.x >= source.x ? 1 : -1;
            return `M ${source.x + direction * nodeHalfWidth} ${source.y} H ${target.x - direction * nodeHalfWidth}`;
        }

        const sourceY = source.y + (target.y >= source.y ? nodeHalfHeight : -nodeHalfHeight);
        const targetY = target.y + (target.y >= source.y ? -nodeHalfHeight : nodeHalfHeight);
        const middleY = sourceY + (targetY - sourceY) / 2;
        return `M ${source.x} ${sourceY} V ${middleY} H ${target.x} V ${targetY}`;
    };

    viewport.append('g').selectAll('path').data(links).join('path')
        .attr('d', linkPath)
        .attr('fill', 'none')
        .attr('stroke', (link) => link.type === 'spouse' ? '#8ba2bb' : '#7898b6')
        .attr('stroke-width', (link) => link.type === 'spouse' ? 2 : 2.5).attr('stroke-dasharray', (link) => link.type === 'spouse' ? '6 4' : null);

    viewport.append('g').selectAll('text.link-label').data(links.filter((link) => link.type === 'spouse')).join('text')
        .attr('class', 'link-label').attr('x', (link) => (link.sourceNode.x + link.targetNode.x) / 2)
        .attr('y', (link) => link.sourceNode.y - 9)
        .attr('text-anchor', 'middle').attr('fill', '#527396').attr('font-size', 10).text('Pasangan');
    viewport.append('g').selectAll('text.child-label').data(links.filter((link) => link.type === 'parent-child' && link.sourceNode?.isRoot).slice(0, 1)).join('text')
        .attr('x', (link) => (link.sourceNode.x + link.targetNode.x) / 2).attr('y', (link) => link.sourceNode.y + nodeHalfHeight + (link.targetNode.y - link.sourceNode.y) / 2)
        .attr('text-anchor', 'middle').attr('fill', '#527396').attr('font-size', 10).text('Anak-anak');

    const groups = viewport.append('g').selectAll('g').data(nodes).join('g')
        .attr('transform', (node) => `translate(${node.x},${node.y})`)
        .attr('tabindex', 0).attr('role', 'button')
        .attr('aria-label', (node) => `Person ${node.name}, ${node.deceased ? 'telah wafat' : 'masih hidup'}`)
        .attr('class', 'cursor-pointer')
        .on('click', (_, node) => emit('select', node))
        .on('keydown', (event, node) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); emit('select', node); } });

    groups.append('rect').attr('x', -92).attr('y', -48).attr('width', 184).attr('height', 96).attr('rx', 14)
        .attr('fill', '#ffffff')
        .attr('stroke', (node) => String(node.id) === String(props.selectedId) ? '#f39a2f' : '#d9e4ef')
        .attr('stroke-width', (node) => String(node.id) === String(props.selectedId) ? 3 : 1.5)
        .attr('opacity', (node) => !match || node.name.toLowerCase().includes(match) ? 1 : 0.35);
    const defs = svg.append('defs');
    nodes.filter((node) => node.photo).forEach((node) => {
        const clipId = `tree-photo-${String(node.id).replace(/[^a-zA-Z0-9_-]/g, '-')}`;
        defs.append('clipPath').attr('id', clipId)
            .attr('clipPathUnits', 'objectBoundingBox')
            .append('circle').attr('cx', 0.5).attr('cy', 0.5).attr('r', 0.5);
    });
    groups.append('circle').attr('cx', -62).attr('cy', 0).attr('r', 27).attr('fill', (node) => node.gender === 'female' ? '#f9dce6' : '#cfe2fb');
    groups.append('circle')
        .attr('class', 'tree-status-indicator')
        .attr('cx', 75)
        .attr('cy', -35)
        .attr('r', 5)
        .attr('fill', (node) => node.deceased ? '#8793a3' : '#22a06b')
        .attr('stroke', '#ffffff')
        .attr('stroke-width', 2)
        .append('title')
        .text((node) => node.deceased ? 'Telah wafat' : 'Masih hidup');
    groups.filter((node) => node.photo).append('image')
        .attr('x', -89).attr('y', -27).attr('width', 54).attr('height', 54)
        .attr('preserveAspectRatio', 'xMidYMid slice')
        .attr('clip-path', (node) => `url(#tree-photo-${String(node.id).replace(/[^a-zA-Z0-9_-]/g, '-')})`)
        .attr('href', (node) => node.photo)
        .attr('xlink:href', (node) => node.photo);
    groups.filter((node) => !node.photo).append('text').attr('x', -62).attr('y', 5).attr('text-anchor', 'middle').attr('font-size', 20).text((node) => node.gender === 'female' ? '♀' : '♂');
    groups.append('text').attr('x', -27).attr('y', -10).attr('fill', '#1c3858').attr('font-size', 12).attr('font-weight', 700).text((node) => node.name.length > 18 ? `${node.name.slice(0, 17)}…` : node.name);
    groups.append('text').attr('x', -27).attr('y', 9).attr('fill', (node) => node.gender === 'female' ? '#e54d91' : '#1687e8').attr('font-size', 15).text((node) => node.gender === 'female' ? '♀' : '♂');
    groups.append('text').attr('x', -12).attr('y', 9).attr('fill', '#607b9b').attr('font-size', 10).text((node) => `${node.birthYear || '—'} – ${node.deathYear || ''}`);
}

function zoomBy(factor) {
    if (svg && zoom) svg.transition().call(zoom.scaleBy, factor);
}

function reset() {
    if (svg && zoom) svg.transition().call(zoom.transform, d3.zoomIdentity);
}

function fitView() {
    if (!svg || !zoom) return;
    svg.transition().call(zoom.transform, d3.zoomIdentity.translate(0, 0).scale(0.85));
}
function center() {
    if (!svg || !zoom) return;
    svg.transition().call(zoom.transform, d3.zoomIdentity.translate(0, 0).scale(1));
}

function svgMarkup() {
    return svg?.node() ? new XMLSerializer().serializeToString(svg.node()) : '';
}

defineExpose({ zoomIn: () => zoomBy(1.25), zoomOut: () => zoomBy(0.8), reset, fitView, center, svgMarkup });

onMounted(render);
watch(() => [props.nodes, props.links, props.selectedId, props.query], render, { deep: true });
onUnmounted(() => {
    if (svg) svg.on('.zoom', null).selectAll('*').remove();
    svg = null;
    zoom = null;
});
</script>

<template>
    <div ref="canvas" class="h-[min(70vh,760px)] min-h-[620px] w-full overflow-hidden rounded-card border border-[#dce7f1] bg-[#f5f9fd]" />
</template>

<style scoped>
:global(.tree-status-indicator) {
    transform-box: fill-box;
    transform-origin: center;
    animation: tree-status-pulse 1.7s ease-in-out infinite;
}

@keyframes tree-status-pulse {
    0%, 100% { opacity: 0.72; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.45); }
}
</style>
