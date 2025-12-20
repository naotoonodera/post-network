function pn_create(
    nodes,
    edges,
    pnOptions,
    optionsMain,
    optionsConfigure,
    optionsEdges,
    optionsGroups,
    optionsInteraction,
    optionsLayout,
    optionsManipulation,
    optionsNodes,
    optionsPhysics
) {
    var container = document.querySelector( "#pn" );
    var data      = {
        nodes: nodes,
        edges: edges,
    };

    var options = Object.assign(
        optionsMain,
        optionsConfigure,
        optionsEdges,
        optionsGroups,
        optionsInteraction,
        optionsLayout,
        optionsManipulation,
        optionsNodes,
        optionsPhysics
    );
    var pn      = new vis.Network( container, data, options );

    if (pnOptions.graph_disable_physics == 1) {
        if (document.readyState === 'complete') {
            const loader = document.querySelector( "#pn-loader" );
            if(loader) {
                setTimeout( function () { loader.remove(); }, 1000 );
            }
        } else {
            window.onload = function () {
                const loader = document.querySelector( "#pn-loader" );
                setTimeout(
                    function () {
                        if(loader) loader.remove();
                    },
                    1000
                );
            };
        }
    } else {
        pn.once(
            "stabilizationIterationsDone",
            function () {
                const loader = document.querySelector( "#pn-loader" );
                setTimeout(
                    function () {
                        if(loader) loader.remove();
                    },
                    1000
                );
            }
        );
    }

    /**
     * Event Listeners
     * https://visjs.github.io/vis-network/docs/network/#Events
     */

    pn.on(
        "doubleClick",
        function (properties) {
            if (Object.keys( properties.nodes ).length !== 0) {
                var ids           = properties.nodes;
                var clickedNodes  = nodes.get( ids );
                var element       = document.getElementById( clickedNodes[0].id );
                if (element) {
                    var elemtop       = element.getBoundingClientRect().top + window.pageYOffset;
                    var targetTop     = elemtop - 32; // - admin bar height
                    var scrollOptions = {
                        left: 0,
                        top: targetTop,
                        behavior: "smooth",
                    };
                    window.scrollTo( scrollOptions );
                }
            }
        }
    );

    pn.on( "click", function (properties) {} );
    pn.on( "oncontext", function (properties) {} );
    pn.on( "dragStart", function (properties) {} );
    pn.on( "dragging", function (properties) {} );
    pn.on( "dragEnd", function (properties) {} );
    pn.on( "controlNodeDragging", function (properties) {} );
    pn.on( "controlNodeDragEnd", function (properties) {} );
    pn.on( "zoom", function (properties) {} );
    pn.on( "showPopup", function (properties) {} );
    pn.on( "hidePopup", function (properties) {} );
    pn.on( "select", function (properties) {} );
    pn.on( "selectNode", function (properties) {} );
    pn.on( "selectEdge", function (properties) {} );
    pn.on( "deselectNode", function (properties) {} );
    pn.on( "deselectEdge", function (properties) {} );
    pn.on( "hoverNode", function (properties) {} );
    pn.on( "hoverEdge", function (properties) {} );
    pn.on( "blurNode", function (properties) {} );
    pn.on( "blurEdge", function (properties) {} );
}


document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.pnData === 'undefined') {
        return;
    }

    var limit = 100;
    var checkVisLoaded = setInterval(function() {
        limit--;
        if (typeof vis !== 'undefined') {
            clearInterval(checkVisLoaded);
            var d = window.pnData;

            var nodes = new vis.DataSet(d.nodes);
            var edges = new vis.DataSet(d.edges);
            pn_create(
                nodes,
                edges,
                d.options,
                d.visConfig.main,
                d.visConfig.configure,
                d.visConfig.edges,
                d.visConfig.groups,
                d.visConfig.interaction,
                d.visConfig.layout,
                d.visConfig.manipulation,
                d.visConfig.nodes,
                d.visConfig.physics
            );
        } else if (limit <= 0) {
            clearInterval(checkVisLoaded);
            console.error("Post Network: Vis.js failed to load.");
        }
    }, 100);
});