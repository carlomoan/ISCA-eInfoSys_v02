// dashboard_current.js
document.addEventListener('DOMContentLoaded', () => {

  // Fetch dashboard API
  fetch(BASE_URL + '/api/dashboard/dashboard_api.php')
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert("Dashboard Error: " + data.error);
        return;
      }

      // ----- 1) Render summary cards -----
      const s = data.summary;
      document.getElementById('totalClusters').textContent = s.total_clusters;
      document.getElementById('totalHouseholds').textContent = s.total_households;
      document.getElementById('currentRound').textContent = s.total_rounds;
      document.getElementById('totalRecords').textContent = s.total_records;
      document.getElementById('totalMosquitoes').textContent = s.total_mosquitoes;

// ----- 2) Pie chart: Species female distribution -----
const pieEl = document.getElementById('pieChart');
if (pieEl) {
  const pieCtx = pieEl.getContext('2d');
  const speciesLabels = Object.keys(data.histogram || {});
  const speciesValues = speciesLabels.map(sp => data.histogram[sp]?.female_total || 0);
  const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#17a2b8'];

  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: speciesLabels,
      datasets: [{
        data: speciesValues,
        backgroundColor: colors
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',       // Legend pembeni ya pie
          labels: {
            font: { size: 10 },    // Ukubwa wa maandishi
            boxWidth: 12,          // Width ya icon ya rangi
            padding: 10            // Umbali kati ya icons na text
          }
        }
      }
    }
  });
}


      // ----- 3) Line chart: Trend per round -----
      const lineEl = document.getElementById('lineTrending');
      if (lineEl) {
        const lineCtx = lineEl.getContext('2d');
        new Chart(lineCtx, {
          type: 'line',
          data: {
            labels: data.trendLabels || [],
            datasets: [{
              label: 'Total Female',
              data: data.trendValues || [],
              borderColor: '#007bff',
              backgroundColor: 'rgba(0,123,255,0.2)',
              fill: true,
              tension: 0.2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, labels: { font: { size: 10 } } } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // ----- 4) Map -----
      const mapContainer = L.DomUtil.get('map');
      if (mapContainer != null) mapContainer._leaflet_id = null; // avoid "already initialized"

      const map = L.map('map').setView([0, 0], 2);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

      const latlngs = [];
      (data.householdTable || []).forEach(hh => {
        if (hh.lat && hh.lng) {
          L.circleMarker([hh.lat, hh.lng], {
            radius: 4,
            fillColor: '#007bff',
            color: '#ffffffff',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8
          })
          .bindPopup(`<b>${hh.hhcode}</b><br>${hh.species}<br>Total: ${hh.total}`)
          .addTo(map);
          latlngs.push([hh.lat, hh.lng]);
        }
      });
      if (latlngs.length > 0) map.fitBounds(latlngs);

      // ----- 5) Cluster table -----
      const clusterTableBody = document.querySelector('#clusterTable tbody');
      clusterTableBody.innerHTML = '';

      (data.clusterLabels || []).forEach(clstname => {
        const tr = document.createElement('tr');
        const totalFemale = (data.clusterTotals?.[clstname] ?? 0);

        // households in this cluster (max 9 only)
        const hhList = (data.householdTable || [])
          .filter(hh => hh.hhcode.startsWith(clstname))
          .slice(0, 9)
          .map(hh => `<li class="hh-link" data-lat="${hh.lat}" data-lng="${hh.lng}" data-species="${hh.species}">${hh.hhcode} (${hh.total})</li>`).join('');

        tr.innerHTML = `
          <td><button class="btn btn-sm btn-outline-primary toggle-households">+</button> 
              <strong>${clstname}</strong></td>
          <td>${totalFemale}</td>
          <td><ul class="household-list" style="display:none">${hhList}</ul></td>
        `;
        clusterTableBody.appendChild(tr);
      });

      // toggle households
      clusterTableBody.querySelectorAll('.toggle-households').forEach(btn => {
        btn.addEventListener('click', () => {
          const ul = btn.closest('tr').querySelector('.household-list');
          if (ul.style.display === 'none') { ul.style.display = 'block'; btn.textContent = '-'; }
          else { ul.style.display = 'none'; btn.textContent = '+'; }
        });
      });

      // click household to zoom on map
      clusterTableBody.querySelectorAll('.hh-link').forEach(li => {
        li.addEventListener('click', () => {
          const lat = parseFloat(li.dataset.lat);
          const lng = parseFloat(li.dataset.lng);
          if (!isNaN(lat) && !isNaN(lng)) {
            map.setView([lat, lng], 16);
            L.popup().setLatLng([lat, lng]).setContent(li.textContent).openOn(map);
          }
        });
      });

      // ----- 6) Initialize DataTable -----
      if (typeof $ !== 'undefined' && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#clusterTable')) $('#clusterTable').DataTable().destroy();
        $('#clusterTable').DataTable({
          scrollY: '250px',
          scrollCollapse: true,
          paging: false,
          searching: true,
          info: false
        });
      }

    })
    .catch(err => {
      console.error('Dashboard fetch error', err);
      alert('Error loading dashboard data');
    });

});
