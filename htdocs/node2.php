<!doctype html>
<html xmlns:xlink="http://www.w3.org/1999/xlink">
<head>
<meta charset="utf-8" />
<style>
    .node circle {
        fill: #fff;
        stroke: steelblue;
        stroke-width: 1.5px;
    }
    .node {
        font: 20px sans-serif;
    }
    .link {
        fill: none;
        stroke: #ccc;
        stroke-width: 1.5px;
    }

    div.tooltip {
        position: absolute;
        text-align: center;
        width: 500px;
        height: 100px;
        padding: 2px;
        font: 12px sans-serif;
        background: lightsteelblue;
        border: 0px;
        border-radius: 8px;
        pointer-events: none;
    }
</style>
</head>
<body>

<script type="text/javascript" src="http://d3js.org/d3.v3.min.js"></script>

    <script type="text/javascript">
    var width = 700;
    var height = 170;
    var cluster = d3.layout.cluster()
        .size([height, width-200]);
    var diagonal = d3.svg.diagonal()
        .projection (function(d) { return [d.y, d.x];});
    var svg = d3.select("body").append("svg")
        .attr("width",width)
        .attr("height",height)
        .attr("style","margin-left: 25ex")
        .append("g")
        .attr("transform","translate(100,0)");
    d3.json("api2.php?id=<?php echo $_GET['id']; ?>", function(error, root){
        var nodes = cluster.nodes(root);
        var links = cluster.links(nodes);
        var link = svg.selectAll(".link")
            .data(links)
            .enter().append("path")
            .attr("class","link")
            .attr("d", diagonal);
        var node = svg.selectAll(".node")
            .data(nodes)
            .enter().append("g")
            .attr("class","node")
            .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });
        node.append("circle")
            .attr("r", 4.5)
            /*.style("fill", function (d) {
                return d.children ? (d.children.some(function (c) { return c.children; }) > 1
                    ? '#1f77b4'
                    : '#ff0000'  )
                    : '';
            })*/;
        node.append("svg:a")
            .attr("xlink:href",function(d){return d.url;})
            .append("text")
                .attr("dx", function(d) { return d.children ? -8 : 8; })
                .attr("dy", 3)
                .style("text-anchor", function(d) { return d.children ? "end" : "start"; })
                .style("font-size","14px")
                .style("text-anchor","middle")
                .attr("dy",15)
                .text( function(d){ return d.name;})
                /*    .append("svg:title")
                    .text(function(d) { return d.quote; });*/
                /*.on("mouseover", function(d) { console.log(d.quote); var nodeSelection = d3.select(this).style({opacity:'0.8'});
                    nodeSelection.select(d.quote).style({opacity:'1.0'}); });*/
            .on("mouseover", function(d) {
                div.transition()
                    .duration(200)
                    .style("opacity", .9);
                div .html(d.metadata + "<br/>")
                    .style("left", (d3.event.pageX) + "px")
                    .style("top", (d3.event.pageY - 28) + "px");
            })
            .on("mouseout", function(d) {
                div.transition()
                    .duration(500)
                    .style("opacity", 0);
            });
    });

    var div = d3.select("body").append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);
</script>

    </body>
</html>