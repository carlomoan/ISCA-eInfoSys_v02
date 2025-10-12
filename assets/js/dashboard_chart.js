// Example helper functions for calling dashboard API
function fetchDashboardData(){
    return $.getJSON('<?= BASE_URL ?>/api/dashboard/dashboard_api.php');
}

function updateSummaryCards(summary){
    $('#summary-cards').empty();
    for(let key in summary){
        $('#summary-cards').append(`<div class="col"><div class="card p-3 text-center">
            <h6>${summary[key].label}</h6>
            <p>${summary[key].value}</p>
        </div></div>`);
    }
}
