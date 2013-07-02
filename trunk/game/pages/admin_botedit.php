<?php


// Графический редактор интеллекта ботов.

function Admin_Botedit ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $result = "";

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
    }

    // Обработка GET-запроса.
    if ( method () === "GET" )
    {
    }

?>

<script type="text/javascript" src="js/go.js"></script>

<script type="text/javascript" id="code">

  function init() {
    var $ = go.GraphObject.make;  // for conciseness in defining templates

    myDiagram = new go.Diagram("myDiagram");  // must name or refer to the DIV HTML element

    // helper definitions for node templates

    // define several shared Brushes
    var graygrad = $(go.Brush, go.Brush.Linear, { 0: "rgb(150, 150, 150)", 0.5: "rgb(86, 86, 86)", 1: "rgb(86, 86, 86)" });
    var greengrad = $(go.Brush, go.Brush.Linear, { 0: "rgb(98, 149, 79)", 1: "rgb(17, 51, 6)" });
    var redgrad = $(go.Brush, go.Brush.Linear, { 0: "rgb(156, 56, 50)", 1: "rgb(82, 6, 0)" });
    var yellowgrad = $(go.Brush, go.Brush.Linear, { 0: "rgb(254, 201, 0)", 1: "rgb(254, 162, 0)" });

    // Don't show shadows on mobile devices for performance reasons
    var shadows = !("ontouchstart" in window);

    function nodeStyle() {
      return {
        // the Node.location is at the center of each node
        locationSpot: go.Spot.Center,
        isShadowed: shadows,
        shadowColor: "#242424",
        // handle mouse enter/leave events to show/hide the ports
        mouseEnter: function(e, obj) { showPorts(obj.part, true); },
        mouseLeave: function(e, obj) { showPorts(obj.part, false); }
      };
    }

    // Define a function for creating a "port" that is normally transparent.
    // The "name" is used as the GraphObject.portId, the "spot" is used to control how links connect
    // and where the port is positioned on the node, and the boolean "output" and "input" arguments
    // control whether the user can draw links from or to the port.
    function makePort(name, spot, output, input) {
      // the port is basically just a small circle that has a white stroke when it is made visible
      return $(go.Shape,
               {
                  figure: "Circle",
                  fill: "transparent",
                  stroke: null,  // this is changed to "white" in the showPorts function
                  desiredSize: new go.Size(6, 6),
                  alignment: spot, alignmentFocus: spot,  // align the port on the main Shape
                  portId: name,  // declare this object to be a "port"
                  fromSpot: spot, toSpot: spot,  // declare where links may connect at this port
                  fromLinkable: output, toLinkable: input,  // declare whether the user may draw links to/from here
                  cursor: "pointer"  // show a different cursor to indicate potential link point
               });
    }

    // define the Node template for regular nodes
    myDiagram.nodeTemplateMap.add("",  // the default category
      $(go.Node, go.Panel.Spot, nodeStyle(),
        // The Node.location comes from the "loc" property of the node data,
        // converted by the Point.parse method.
        // If the Node.location is changed, it updates the "loc" property of the node data,
        // converting back using the Point.stringify method.
        new go.Binding("location", "loc", go.Point.parse).makeTwoWay(go.Point.stringify),
        // the main object is a Panel that surrounds a TextBlock with a rectangular Shape
        $(go.Panel, go.Panel.Auto,
          $(go.Shape, "Rectangle",
            { fill: graygrad },
            new go.Binding("figure", "figure")),
          $(go.TextBlock,
            { font: "bold 9pt Helvetica, Arial, sans-serif",
              stroke: "white",
              margin: 8,
              maxSize: new go.Size(100, NaN),
              wrap: go.TextBlock.WrapFit,
              editable: true },
            new go.Binding("text", "text").makeTwoWay())),
        // four named ports, one on each side:
        makePort("T", go.Spot.Top, false, true),
        makePort("L", go.Spot.Left, true, true),
        makePort("R", go.Spot.Right, true, true),
        makePort("B", go.Spot.Bottom, true, false)
        ));

    myDiagram.nodeTemplateMap.add("Start",
      $(go.Node, go.Panel.Spot, nodeStyle(),
        new go.Binding("location", "loc", go.Point.parse).makeTwoWay(go.Point.stringify),
        $(go.Panel, go.Panel.Auto,
          $(go.Shape, "Ellipse",
            { fill: greengrad, stroke: "rgb(17, 51, 6)" }),
          $(go.TextBlock, "Start",
            { margin: 5,
              font: "bold 9pt Helvetica, Arial, sans-serif",
              stroke: "rgb(190, 247, 112)" })),
        // three named ports, one on each side except the top, all output only:
        makePort("L", go.Spot.Left, true, false),
        makePort("R", go.Spot.Right, true, false),
        makePort("B", go.Spot.Bottom, true, false)
        ));

    myDiagram.nodeTemplateMap.add("End",
      $(go.Node, go.Panel.Spot, nodeStyle(),
        new go.Binding("location", "loc", go.Point.parse).makeTwoWay(go.Point.stringify),
        $(go.Panel, go.Panel.Auto,
          $(go.Shape, "Ellipse",
            { fill: redgrad, stroke: "rgb(82, 6, 0)" }),
          $(go.TextBlock, "End",
            { margin: 5,
              font: "bold 9pt Helvetica, Arial, sans-serif",
              stroke: "rgb(255, 207, 169)" })),
        // three named ports, one on each side except the bottom, all input only:
        makePort("T", go.Spot.Top, false, true),
        makePort("L", go.Spot.Left, false, true),
        makePort("R", go.Spot.Right, false, true)
        ));

    myDiagram.nodeTemplateMap.add("Comment",
      $(go.Node, go.Panel.Auto, nodeStyle(),
        new go.Binding("location", "loc", go.Point.parse).makeTwoWay(go.Point.stringify),
        $(go.Shape, "Ellipse",
          { fill: yellowgrad },
          new go.Binding("figure", "figure")),
        $(go.TextBlock,
          { margin: 5,
            maxSize: new go.Size(200, NaN),
            wrap: go.TextBlock.WrapFit,
            textAlign: "center",
            editable: true,
            font: "bold 9pt Helvetica, Arial, sans-serif" },
          new go.Binding("text", "text").makeTwoWay())
        // no ports, because no links are allowed to connect with a comment
        ));


    // replace the default Link template in the linkTemplateMap
    myDiagram.linkTemplate =
      $(go.Link,  // the whole link panel
        { routing: go.Link.AvoidsNodes,
          curve: go.Link.JumpOver,
          corner: 5, toShortLength: 4,
          relinkableFrom: true, relinkableTo: true, reshapable:true },
        $(go.Shape,  // the link path shape
          { isPanelMain: true,
            stroke: "whitesmoke", strokeWidth: 2 }),
        $(go.Shape,  // the arrowhead
          { toArrow: "standard",
            stroke: null, fill: "whitesmoke" }),
        $(go.Panel, go.Panel.Auto,
          { visible: false, name: "LABEL", segmentIndex: 2, segmentFraction: 0.5},
          new go.Binding("visible", "visible").makeTwoWay(),
          $(go.Shape, "RoundedRectangle",  // the link shape
            { fill: "#F8F8F8", stroke: null }),
          $(go.TextBlock, "Yes",  // the label
            { textAlign: "center",
              font: "10pt helvetica, arial, sans-serif",
              stroke: "#919191",
              margin: 2, editable: true },
            new go.Binding("text", "text").makeTwoWay())
          )
        );

    // make link labels visible if coming out of a "conditional" node
    myDiagram.addDiagramListener("LinkDrawn", function(e) {
      if (e.subject.fromNode.data.figure === "Diamond") {
        var label = e.subject.findObject("LABEL");
        if (label !== null) label.visible = true;
      }
    })

    myDiagram.allowDrop = true;  // must be true to accept drops from the Palette
    // temporary links used by LinkingTool and RelinkingTool are also orthogonal:
    myDiagram.toolManager.linkingTool.temporaryLink.routing = go.Link.Orthogonal;
    myDiagram.toolManager.relinkingTool.temporaryLink.routing = go.Link.Orthogonal;

    load();  // load an initial diagram from some JSON text

    // initialize the Palette that is on the left side of the page
    myPalette = new go.Palette("myPalette");  // must name or refer to the DIV HTML element
    myPalette.nodeTemplateMap = myDiagram.nodeTemplateMap;  // share the templates used by myDiagram
    myPalette.model = new go.GraphLinksModel([  // specify the contents of the Palette
      { category: "Start", text: "Start" },
      { text: "Step" },
      { text: "???", figure: "Diamond" },
      { category: "End", text: "End" },
      { category: "Comment", text: "Comment", figure: "RoundedRectangle" }
    ]);
  }

  // Make all ports on a node visible when the mouse is over the node
  function showPorts(node, show) {
    var diagram = node.diagram;
    if (!diagram || diagram.isReadOnly || !diagram.allowLink) return;
    var it = node.ports;
    while (it.next()) {
      var port = it.value;
      port.stroke = (show ? "white" : null);
    }
  }

  // Show the diagram's model in JSON format that the user may have edited
  function save() {
    var str = myDiagram.model.toJson();
    document.getElementById("mySavedModel").value = str;
  }
  function load() {
    var str = document.getElementById("mySavedModel").value;
    myDiagram.model = go.Model.fromJson(str);
    myDiagram.undoManager.isEnabled = true;
  }
</script>


<?=AdminPanel();?>

<div id="sample">
  <div style="width:100%; white-space:nowrap;">
    <span style="display: inline-block; vertical-align: top; padding: 5px; width:100px">
      <div id="myPalette" style="background-color: #344566; border: solid 1px black; height: 500px"></div>
    </span>
    <span style="display: inline-block; vertical-align: top; padding: 5px; width:88%">
      <div id="myDiagram" style="background-color: #344566; border: solid 1px black; height: 500px"></div>
    </span>
  </div>
<span style="float:right;">
  <button onclick="save()">Сохранить</button>
<select>
<option value="1">Развитие до малого транспорта</option>
<option value="2">Развитие до колонизатора</option>
</select>
  <button onclick="load()">Загрузить</button>
</span>
  <textarea id="mySavedModel" style="width:100%;height:300px; display:none;">
{ "class": "go.GraphLinksModel",
  "linkFromPortIdProperty": "fromPort",
  "linkToPortIdProperty": "toPort",
  "nodeDataArray": [ ],
  "linkDataArray": [ ]}
  </textarea>
</div>

<script type="text/javascript">
init ();
</script>

<?php
}
?>