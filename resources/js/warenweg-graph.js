import cytoscape from 'cytoscape';
import dagre from 'cytoscape-dagre';

cytoscape.use(dagre);

/**
 * Alpine component powering the "Warenweg" traceability graph.
 *
 * Renders a layered left→right supply-chain DAG (Lieferant → Lieferung →
 * Gebinde → Abfüllung → Produkt). Goods flow left→right and the edges animate
 * that flow with marching dashes. The selected entity is the anchor and is
 * emphasised; compliance gaps are painted loudly in red. Clicking a node opens
 * a detail modal (driven by the `detailNode` Alpine state the Blade view reads).
 *
 * Colours are read from Filament's CSS custom properties so the graph tracks
 * the panel's light/dark theme automatically.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('warenwegGraph', warenwegGraph);
});

export default function warenwegGraph({ nodes, edges, anchor, currentTargetId = null }) {
    return {
        cy: null,
        anchorId: anchor,
        currentTargetId,
        detailNode: null,
        detailLoading: false,
        modalOpen: false,

        // Above this node count we drop the expensive eye-candy (per-frame edge
        // animation, drop shadows, full-res paint during pan/zoom) so bigger
        // graphs stay responsive. Small graphs keep the full treatment.
        heavy: false,

        init() {
            if (!nodes || nodes.length === 0) {
                return;
            }

            this.heavy = nodes.length > 20;

            this.cy = cytoscape({
                container: this.$refs.canvas,
                elements: { nodes, edges },
                minZoom: 0.15,
                maxZoom: 3,
                // Built-in wheel zoom is disabled; we drive a stronger,
                // cursor-anchored zoom ourselves (see wireInteractions).
                userZoomingEnabled: false,
                // Crisp on HiDPI for normal graphs; cap at 1 for large ones so we
                // are not pushing 4× the pixels every frame.
                pixelRatio: this.heavy ? 1 : (window.devicePixelRatio || 1),
                // Always live-render: the viewport texture snapshots an opaque
                // fill and slides it during drag (looks like moving the whole
                // canvas). Heavy mode stays cheap via no edge animation / no
                // shadows / pixelRatio 1 instead.
                textureOnViewport: false,
                hideEdgesOnViewport: false,
                // The layout is meaningful (goods flow left→right), so nodes are
                // fixed — the user pans and zooms but cannot drag them around.
                autoungrabify: true,
                boxSelectionEnabled: false,
                style: this.buildStyle(),
                layout: this.layoutOptions(),
            });

            if (!this.heavy) {
                this.animateFlow();
            }
            this.wireInteractions();
            this.focusAnchor();

            new ResizeObserver(() => this.cy?.resize()).observe(this.$refs.canvas);

            new MutationObserver(() => this.cy?.style(this.buildStyle()))
                .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        layoutOptions() {
            return {
                name: 'dagre',
                rankDir: 'LR',
                nodeSep: 34,
                rankSep: 120,
                edgeSep: 16,
                padding: 32,
                ranker: 'network-simplex',
            };
        },

        /**
         * Resolve a Filament CSS custom property to a concrete colour string.
         * The theme may store it either as an `r g b` triplet (older Filament)
         * or as a full colour like `oklch(...)` (v4 themes). We detect which and
         * hand Cytoscape a valid value — never `rgb(oklch(...))`.
         */
        css(name, fallback) {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            if (!value) return fallback;
            // A bare "r g b" / "r, g, b" triplet needs wrapping; anything with a
            // function name (oklch/rgb/hsl/…) or hex is already a valid colour.
            return /^[\d.]+[\s,]+[\d.]+[\s,]+[\d.]+$/.test(value) ? `rgb(${value})` : value;
        },

        buildStyle() {
            const primary = this.css('--primary-500', 'rgb(109,139,92)');
            const primaryDim = this.css('--primary-400', 'rgb(142,170,126)');
            const danger = 'rgb(220,38,38)';
            const dark = document.documentElement.classList.contains('dark');

            const surface = dark ? '#1a2331' : '#ffffff';
            const title = dark ? '#f1f5f9' : '#0f172a';
            const edge = dark ? '#475569' : '#cbd5e1';

            // One system: green accents everywhere, red only when a node carries
            // a compliance gap.
            const accentOf = (n) => (n.data('gap') ? danger : primary);

            // Drop shadows are expensive to composite per node; only on small graphs.
            const shadow = this.heavy
                ? { 'shadow-blur': 0, 'shadow-opacity': 0 }
                : {
                    'shadow-blur': 12,
                    'shadow-color': dark ? '#000000' : '#334155',
                    'shadow-opacity': dark ? 0.45 : 0.12,
                    'shadow-offset-y': 2,
                };

            return [
                {
                    selector: 'node',
                    style: {
                        shape: 'round-rectangle',
                        'corner-radius': 12,
                        // Size to content instead of a fixed box.
                        width: 'label',
                        height: 'label',
                        'padding-left': 18,
                        'padding-right': 18,
                        'padding-top': 14,
                        'padding-bottom': 14,
                        'background-color': surface,
                        'background-opacity': 1,
                        'border-width': (n) => (n.data('gap') ? 2 : 1.5),
                        'border-color': accentOf,
                        ...shadow,
                        label: (n) => this.nodeLabel(n),
                        'text-wrap': 'wrap',
                        'text-max-width': 190,
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'text-justification': 'left',
                        color: title,
                        'font-size': 13,
                        'font-weight': 600,
                        'line-height': 1.4,
                        'transition-property': 'border-color, border-width, shadow-opacity',
                        'transition-duration': '150ms',
                    },
                },
                {
                    selector: 'node[?matched]',
                    style: {
                        'border-width': 3,
                        'border-color': primary,
                        'shadow-blur': 24,
                        'shadow-color': primaryDim,
                        'shadow-opacity': 0.5,
                        'shadow-offset-y': 0,
                    },
                },
                {
                    selector: 'node:active',
                    style: { 'overlay-opacity': 0.08, 'overlay-color': primary, 'overlay-padding': 6 },
                },
                {
                    selector: 'edge',
                    style: {
                        width: 2,
                        'line-color': (e) => (e.data('gap') ? danger : edge),
                        'target-arrow-color': (e) => (e.data('gap') ? danger : edge),
                        'target-arrow-shape': 'triangle-backcurve',
                        'arrow-scale': 1,
                        'curve-style': 'bezier',
                        'control-point-step-size': 60,
                        'line-style': 'dashed',
                        'line-dash-pattern': [6, 6],
                        'line-cap': 'round',
                    },
                },
            ];
        },

        typeName(type) {
            return {
                supplier: 'LIEFERANT',
                delivery: 'LIEFERUNG',
                herb: 'ROHSTOFF',
                bag: 'GEBINDE',
                filling: 'ABFÜLLUNG',
                product: 'PRODUKT',
            }[type] ?? '';
        },

        nodeLabel(n) {
            const parts = [this.typeName(n.data('type'))];
            parts.push(n.data('label'));
            if (n.data('sublabel')) parts.push(n.data('sublabel'));
            if (n.data('meta')) parts.push(n.data('meta'));
            return parts.filter(Boolean).join('\n');
        },

        /**
         * Marching-ants: dashes crawl gently along every edge in the goods-flow
         * direction (source → target, i.e. left → right). Speed is time-based
         * (px per second) so it is smooth and independent of frame rate.
         */
        animateFlow() {
            const speed = 14; // px/second — deliberately slow/calm
            let last = null;
            let offset = 0;
            const tick = (now) => {
                // Skip work when the tab is hidden — no point animating offscreen.
                if (document.hidden) {
                    last = now;
                    this._raf = requestAnimationFrame(tick);
                    return;
                }
                if (last !== null) {
                    offset = (offset - (speed * (now - last)) / 1000) % 1000;
                    this.cy?.edges().style('line-dash-offset', offset);
                }
                last = now;
                this._raf = requestAnimationFrame(tick);
            };
            this._raf = requestAnimationFrame(tick);
        },

        wireInteractions() {
            this.cy.on('tap', 'node', async (event) => {
                this.detailNode = event.target.data();
                this.detailLoading = true;
                this.modalOpen = true;

                // Rich detail is computed server-side on demand so the graph
                // build stays cheap. Fetch it for the clicked node.
                try {
                    this.detailNode = {
                        ...this.detailNode,
                        detail: await this.$wire.nodeDetail(this.detailNode.id),
                    };
                } finally {
                    this.detailLoading = false;
                }
            });

            this.cy.on('mouseover', 'node', () => {
                this.$refs.canvas.style.cursor = 'pointer';
            });
            this.cy.on('mouseout', 'node', () => {
                this.$refs.canvas.style.cursor = 'grab';
            });

            // Custom wheel zoom: a firm 12% step per notch, anchored at the
            // cursor. Trackpads send many small deltas, mice a few large ones —
            // scaling by deltaY keeps both feeling right.
            this.$refs.canvas.addEventListener('wheel', (e) => {
                e.preventDefault();
                const rect = this.$refs.canvas.getBoundingClientRect();
                const factor = Math.exp(-e.deltaY * 0.002);
                const next = Math.min(3, Math.max(0.15, this.cy.zoom() * factor));
                this.cy.zoom({
                    level: next,
                    renderedPosition: { x: e.clientX - rect.left, y: e.clientY - rect.top },
                });
            }, { passive: false });
        },

        focusAnchor() {
            const node = this.anchorId ? this.cy.getElementById(this.anchorId) : null;
            if (node && node.nonempty()) {
                this.cy.animate({ center: { eles: node }, zoom: 1 }, { duration: 350 });
            } else {
                this.fit();
            }
        },

        /**
         * Fit the whole graph into view, but never zoom in past a comfortable
         * level — a tiny 3-node graph should not fill the canvas at huge scale.
         */
        fit() {
            if (!this.cy) return;
            this.cy.fit(undefined, 40);
            if (this.cy.zoom() > 1.2) {
                this.cy.zoom(1.2);
                this.cy.center();
            }
        },

        recenter() {
            this.focusAnchor();
        },

        closeModal() {
            this.modalOpen = false;
        },

        destroy() {
            cancelAnimationFrame(this._raf);
            this.cy?.destroy();
        },
    };
}
