<!-- Print Confirmation Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header bg-light">
        <h5 class="modal-title">Print Confirmation</h5>
        <button type="button" class="btn-close" id="closePrintModal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div id="modal-print-area" class="p-3">
          <!-- JS will inject dynamic confirmation table here -->
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="cancelPrintModal">Close</button>
        <button id="confirmPrintBtn" type="button" class="btn btn-dark">
          <i class="bi bi-printer"></i> Print
        </button>
      </div>

    </div>
  </div>
</div>
