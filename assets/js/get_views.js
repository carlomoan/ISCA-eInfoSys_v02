$(document).ready(function () {

    // =====================
    // Load Rounds Dropdown
    // =====================
    function loadRounds() {
        $.ajax({
            url: BASE_URL + '/api/dataviewsapi/get_rounds_api.php',
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    let $select = $('#filter-round');
                    $select.empty();
                    $select.append('<option value="0">All</option>');
                    res.data.forEach(function(round){
                        $select.append('<option value="'+round+'">Round '+round+'</option>');
                    });

                    // Load initial data after rounds loaded
                    loadViews(0, '');
                } else {
                    console.error('Error loading rounds:', res.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading rounds:', error);
            }
        });
    }

    // =====================
    // Load Data Table
    // =====================
    function loadViews(round = 0, search = '') {
        $.ajax({
            url: BASE_URL + '/api/dataviewsapi/get_views_api.php',
            method: 'GET',
            data: { round: round, search: search },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let data = response.data;

                    if (data.length === 0) {
                        $('#views-table-container').html('<p>No data found.</p>');
                        return;
                    }

                    // Dynamically create columns from first row
                    let columns = Object.keys(data[0]).map(key => ({
                        title: key,
                        data: key,
                        render: function(value) {
                            // Format timestamps
                            if (value === "0000-00-00" || value === "0000-00-00 00:00:00") return "";
                            return value;
                        }
                    }));

                    // Destroy existing table if exists
                    if ($.fn.DataTable.isDataTable('#views-table')) {
                        $('#views-table').DataTable().clear().destroy();
                    }

                    // Build table HTML
                    let html = '<table id="views-table" class="reports-table display nowrap" style="width:100%"><thead><tr>';
                    columns.forEach(col => {
                        html += `<th>${col.title}</th>`;
                    });
                    html += '</tr></thead><tbody></tbody></table>';
                    $('#views-table-container').html(html);

                    // Initialize DataTable
                    $('#views-table').DataTable({
                        data: data,
                        columns: columns,
                        searching: false,
                        paging: true,
                        scrollX: true,
                        order: [[0, 'desc']],
                        info: true,
                        fixedHeader: true,
                        responsive: true,
                        autoWidth: false,
                        columnDefs: columns.map((col, idx) => ({ width: '150px', targets: idx }))
                    });
                } else {
                    $('#views-table-container').html('<p>Error: ' + response.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#views-table-container').html('<p>AJAX error: ' + error + '</p>');
            }
        });
    }

    // =====================
    // Event Listeners
    // =====================
    $('#filter-round').on('change', function() {
        let round = parseInt($(this).val()) || 0;
        let search = $('#filter-search').val().trim();
        loadViews(round, search);
    });

    $('#filter-search').on('keyup', function() {
        let search = $(this).val().trim();
        let round = parseInt($('#filter-round').val()) || 0;
        loadViews(round, search);
    });

    // =====================
    // Initial Load
    // =====================
    loadRounds();

});
