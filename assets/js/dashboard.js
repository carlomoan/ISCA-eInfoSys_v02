// dashboard.js

$(document).ready(function(){

    // ===== Prepare charts =====
    initHistogramChart();
    initSpeciesPerClusterChart();
    initTrendingChart();

    // ===== Initialize map =====
    renderMap();

    // ===== DataTables for cluster table =====
    $('#clusterTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        order: [[0,'asc']],
        info: false,
        scrollX: true
    });

});

// ===== Histogram (Feeding states) =====
function initHistogramChart(){
    const ctx = document.getElementById('histogramChart').getContext('2d');
    const labels = Object.keys(histogram);
    const datasets = [
        { label:'Fed', data: labels.map(s=>histogram[s].fed), backgroundColor:'#1f77b4' },
        { label:'Unfed', data: labels.map(s=>histogram[s].unfed), backgroundColor:'#ff7f0e' },
        { label:'Gravid', data: labels.map(s=>histogram[s].gravid), backgroundColor:'#2ca02c' },
        { label:'SemiGravid', data: labels.map(s=>histogram[s].semi_gravid), backgroundColor:'#d62728' }
    ];
    new Chart(ctx,{
        type:'bar',
        data:{labels,datasets},
        options:{
            responsive:true,
            plugins:{ title:{ display:true, text:'Feeding states (female only) by species — total counts' } },
            scales:{ x:{ stacked:false }, y:{ beginAtZero:true, title:{ display:true, text:'Female count (absolute)' } } }
        }
    });
}

// ===== Species per cluster =====
function initSpeciesPerClusterChart(){
    const ctx = document.getElementById('lineSpeciesCluster').getContext('2d');
    const colors = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b'];
    const datasets = [];
    let i=0;
    for(const sp in clusterSeries){
        datasets.push({
            label: sp,
            data: clusterSeries[sp],
            borderColor: colors[i%colors.length],
            backgroundColor: colors[i%colors.length],
            fill:false,
            tension:0.3
        });
        i++;
    }
    new Chart(ctx,{ type:'line', data:{ labels: clusterLabels, datasets }, options:{ responsive:true, plugins:{ title:{ display:true, text:'Female species counts per cluster' } } } });
}

// ===== Trending per round =====
function initTrendingChart(){
    const ctx = document.getElementById('lineTrending').getContext('2d');
    new Chart(ctx,{
        type:'line',
        data:{ labels: trendLabels, datasets:[{ label:'Female total', data: trendValues, borderColor:'#1f77b4', fill:false, tension:0.25 }] },
        options:{ responsive:true, plugins:{ title:{ display:true, text:'Female total per round' } }, scales:{ y:{ beginAtZero:true } } }
    });
}

// ===== Leaflet Map =====
function renderMap(){
    var map = L.map('map').setView([-6.8,39.2],7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);

    var markersCluster = L.markerClusterGroup();
    var hhLeafMarkers = {};

    function getColor(count){ return count<=50?'green':count<=150?'orange':'red'; }
    function getRadius(count){ return 5 + Math.min(count/20,20); }

    hhMarkers.forEach(function(c){
        if(!c.lat || !c.lng) return;
        var circle = L.circleMarker([c.lat,c.lng],{
            radius:getRadius(c.total),
            color:getColor(c.total),
            fillColor:getColor(c.total),
            fillOpacity:0.7
        }).bindPopup(`<b>HHCode: ${c.hhcode}</b><br>Species: ${c.speciesSummary}`);
        circle.hhData = c;
        markersCluster.addLayer(circle);
        hhLeafMarkers[c.hhcode] = circle;
    });

    map.addLayer(markersCluster);

    // Cluster popup aggregate
    markersCluster.on('clusterclick', function(a){
        var cluster = a.layer;
        var markers = cluster.getAllChildMarkers();
        var aggSpecies = {};
        markers.forEach(function(m){
            var sParts = m.hhData.speciesSummary.split(', ');
            sParts.forEach(function(p){
                var [sp,val] = p.split(': ');
                val = parseInt(val);
                if(!aggSpecies[sp]) aggSpecies[sp]=0;
                aggSpecies[sp]+=val;
            });
        });
        var hhCount = markers.length;
        var aggText = Object.entries(aggSpecies).map(([k,v])=>`${k}: ${v}`).join('<br>');
        L.popup()
         .setLatLng(cluster.getLatLng())
         .setContent(`<b>Cluster Info</b><br>Total HH: ${hhCount}<br>${aggText}`)
         .openOn(map);
    });

    // Table HH links
    $('.hh-link').on('click', function(){
        const lat = parseFloat($(this).data('lat'));
        const lng = parseFloat($(this).data('lng'));
        const species = $(this).data('species');
        const hhcode = $(this).text().split(" ")[0];
        if(!isNaN(lat) && !isNaN(lng)){
            map.setView([lat,lng],15);
            L.popup()
             .setLatLng([lat,lng])
             .setContent(`<b>HHCode: ${hhcode}</b><br>Species: ${species}`)
             .openOn(map);
        }
    });
}
