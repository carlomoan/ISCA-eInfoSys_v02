// assets/js/dashboard_map.js
document.addEventListener('DOMContentLoaded', () => {

  // Helper function to safely initialize DataTable
  function initDataTable(selector) {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
      // destroy if already initialized
      if ($.fn.DataTable.isDataTable(selector)) $(selector).DataTable().destroy();
      $(selector).DataTable({
        scrollY: '250px',
        scrollCollapse: true,
        paging: false,
        searching: true,
        info: false
      });
    } else {
      console.warn('DataTable not loaded. Include jQuery and DataTables before this script.');
    }
  }

  fetch(BASE_URL + '/api/dashboard/dashboard_map_api.php')
    .then(res => res.json())
    .then(data => {
      
      // ====== Map Initialization ======
      const mapContainer = L.DomUtil.get('map');
      if (mapContainer != null) mapContainer._leaflet_id = null; // avoid already initialized
      const map = L.map('map').setView([-5.07, 39.10], 12);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

      // ====== Deduplicate households ======
      const seen = new Set();
      const households = (data.householdTable || []).filter(hh => {
        if (!hh.hhcode) return false;
        if (seen.has(hh.hhcode)) return false;
        seen.add(hh.hhcode);
        return true;
      });

      // ====== Heatmap ======
      const heatPoints = households
        .filter(hh => hh.latitude && hh.longitude)
        .map(hh => [hh.latitude, hh.longitude, hh.total || 1]);
      if (heatPoints.length > 0 && typeof L.heatLayer === 'function') {
        L.heatLayer(heatPoints, { radius: 25, blur: 15, maxZoom: 17 }).addTo(map);
      }

      // ====== Dots per household + markers reference ======
      const markers = {}; // key = hhcode, value = marker
      households.forEach(hh => {
        if (hh.latitude && hh.longitude) {
          const marker = L.circleMarker([hh.latitude, hh.longitude], {
            radius: 5,
            fillColor: '#007bff',
            color: '#fff',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.9
          }).addTo(map);

          const clusterInfo = (data.clusterTotals || []).find(c => c.cluster_id == hh.cluster_id);
          const popupText = clusterInfo
            ? `<b>${clusterInfo.cluster_name}</b><br>HH Code: ${hh.hhcode}<br>Total female: ${clusterInfo.total_female}`
            : `HH Code: ${hh.hhcode}`;

          marker.bindPopup(popupText);
          markers[hh.hhcode] = marker; // save reference
        }
      });

      // fit map bounds
      const allLatLngs = households.filter(hh => hh.latitude && hh.longitude).map(hh => [hh.latitude, hh.longitude]);
      if (allLatLngs.length > 0) map.fitBounds(allLatLngs);

      // ====== Cluster Table ======
      const clusterTableBody = document.querySelector('#clusterTable tbody');
      clusterTableBody.innerHTML = '';

      (data.clusterTotals || []).forEach(cluster => {
        const hhList = households
          .filter(hh => hh.cluster_id == cluster.cluster_id)
          .map(hh => `<li class="hh-item" data-hhcode="${hh.hhcode}">${hh.hhcode} (${hh.total})</li>`)
          .join('');

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><button class="btn btn-sm btn-outline-primary toggle-households">+</button>
              <strong>${cluster.cluster_name}</strong></td>
          <td>${cluster.total_female}</td>
          <td><ul class="household-list" style="display:none">${hhList}</ul></td>
        `;
        clusterTableBody.appendChild(tr);
      });

      // ====== Toggle households ======
      clusterTableBody.querySelectorAll('.toggle-households').forEach(btn => {
        btn.addEventListener('click', () => {
          const ul = btn.closest('tr').querySelector('.household-list');
          if (ul.style.display === 'none') { ul.style.display = 'block'; btn.textContent = '-'; }
          else { ul.style.display = 'none'; btn.textContent = '+'; }
        });
      });

      // ====== Zoom to household on click ======
      clusterTableBody.addEventListener('click', e => {
        if (e.target.classList.contains('hh-item')) {
          const hhcode = e.target.dataset.hhcode;
          const marker = markers[hhcode];
          if (marker) {
            map.setView(marker.getLatLng(), 17, { animate: true });
            marker.openPopup();
          }
        }
      });

      // ====== Initialize DataTable safely ======
      initDataTable('#clusterTable');

    })
    .catch(err => {
      console.error('Dashboard fetch error', err);
      alert('Error loading dashboard data');
    });

});
