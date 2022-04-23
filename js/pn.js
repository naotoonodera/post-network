function pn_create(
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
		window.onload = function () {
			const loader = document.querySelector( "#pn-loader" );
			setTimeout(
				function () {
					loader.remove();
				},
				1000
			);
		};
	} else {
		pn.once(
			"stabilizationIterationsDone",
			function () {
				const loader = document.querySelector( "#pn-loader" );
				setTimeout(
					function () {
						loader.remove();
					},
					1000
				);
			}
		);
	}

	/**
	 * 	Event Listeners
	 *  https://visjs.github.io/vis-network/docs/network/#Events
	 *
	 *	Structure of event properties
	 *	{
	 *		nodes: [Array of selected nodeIds],
	 *		edges: [Array of selected edgeIds],
	 *		event: [Object] original click event,
	 *		pointer: {
	 *			DOM: {x:pointer_x, y:pointer_y},
	 *			canvas: {x:canvas_x, y:canvas_y}
	 *		}
	 *		items: [Array of click items],
	 *		**note: "items" only included for "click" event. Following items possible to receive
	 *			{nodeId:NodeId}            // node with given id clicked on
	 *			{nodeId:NodeId labelId:0}  // label of node with given id clicked on
	 *			{edgeId:EdgeId}            // edge with given id clicked on
	 *			{edge:EdgeId, labelId:0}   // label of edge with given id clicked on
	 *	}
	 */

	pn.on(
		"doubleClick",
		function (properties) {
			if (Object.keys( properties.nodes ).length !== 0) {
				var ids           = properties.nodes;
				var clickedNodes  = nodes.get( ids );
				var element       = document.getElementById( clickedNodes[0].id );
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
