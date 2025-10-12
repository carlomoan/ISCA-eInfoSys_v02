<!-- ==== Verify and Merge Data ==== -->
<div class="tab-content" id="tab-desk_compare_merge">
    <h3>Field and Laboratory Verification and Merging</h3>
    <?php if (!$canMergedDesk): ?>
        <p><em>You do not have permission to verify or merge data.</em></p>
    <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <input type="text" class="custom-search-desk-merge" 
                       data-tab="desk_compare_merge" 
                       placeholder="Type to search..." />
            </div>
        </div>

        <form id="compare-desk-merge-form" class="merge-form"
              action="<?= BASE_URL ?>/api/deskmergeapi/desk_merge_api.php"
              method="POST" enctype="multipart/form-data">
            
            <p class="info-note">
                This process will compare all records stored in 
                <strong>desk_field_collector</strong> and 
                <strong>desk_lab_sorter</strong>. 
                Matched data will be merged into the final tables 
                (<em>field_collector</em> & <em>lab_sorter</em>), 
                while mismatches will be highlighted below.
            </p>

            <button type="submit" class="btn-compare">Compare &amp; Merge</button>
        </form>

        <!-- Feedback & Result Section -->
        <div id="merge-feedback" class="feedback-section" style="margin-top:1rem; display:none;">
            <div class="summary-box">
                <h4>Merge Summary</h4>
                <ul>
                    <li><strong>Round:</strong> <span id="merge-round">-</span></li>
                    <li><strong>Matched Records:</strong> <span id="merge-matched-count">0</span></li>
                    <li><strong>Mismatched (Field Only):</strong> <span id="merge-field-only-count">0</span></li>
                    <li><strong>Mismatched (Lab Only):</strong> <span id="merge-lab-only-count">0</span></li>
                </ul>
            </div>

            <div class="table-preview">
                <h4>Mismatched Records</h4>
                <table id="merge-mismatched-table" class="display compact" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Household Code</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS will populate mismatched data -->
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
