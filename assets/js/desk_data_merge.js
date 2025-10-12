document.addEventListener("DOMContentLoaded", () => {

    // ==== Show Confirmation Modal (Stage 5) ====
    window.showConfirmation = function(fieldData, labData = {}) {
        const modal = document.getElementById("confirmationModal");
        const fieldTableBody = modal.querySelector("#fieldDataTable tbody");
        const labTableBody = modal.querySelector("#labDataTable tbody");

        if (!modal || !fieldTableBody || !labTableBody) {
            console.error("Confirmation modal or table elements not found.");
            return;
        }

        // Clear old data
        fieldTableBody.innerHTML = "";
        labTableBody.innerHTML = "";

        // Populate field data
        Object.entries(fieldData).forEach(([key, value]) => {
            const row = `<tr><td>${key}</td><td>${value ?? "-"}</td></tr>`;
            fieldTableBody.insertAdjacentHTML("beforeend", row);
        });

        // Populate lab data
        Object.entries(labData).forEach(([key, value]) => {
            const row = `<tr><td>${key}</td><td>${value ?? "-"}</td></tr>`;
            labTableBody.insertAdjacentHTML("beforeend", row);
        });

        // Show modal
        modal.classList.remove("hidden");

        // Cancel
        const cancelBtn = modal.querySelector("#cancelSubmit");
        cancelBtn?.addEventListener("click", () => {
            modal.classList.add("hidden");
        });

        // Print
        const printBtn = modal.querySelector("#printConfirmation");
        printBtn?.addEventListener("click", () => {
            const w = window.open("");
            w.document.write(`<html><head><title>Confirmation</title></head><body>${modal.innerHTML}</body></html>`);
            w.print();
            w.close();
        });

        // Confirm & Submit → Stage 5
        const submitBtn = modal.querySelector("#confirmSubmit");
        submitBtn?.addEventListener("click", async () => {
            try {
                // 1️⃣ Insert into desk_field_collector
                const res = await fetch(BASE_URL + "/api/deskfieldapi/add_field_data_api.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(fieldData)
                });
                const json = await res.json();

                if (json.success) {
                    // 2️⃣ Update latest round
                    const roundUpdateData = { 
                        hhcode: fieldData.hhcode, 
                        clstid: fieldData.clstid, 
                        round: fieldData.round 
                    };
                    try {
                        const roundRes = await fetch(BASE_URL + "/api/deskfieldapi/update_latest_round_api.php", {
                            method: "POST",
                            headers: {"Content-Type":"application/json"},
                            body: JSON.stringify(roundUpdateData)
                        });
                        const roundJson = await roundRes.json();
                        if(roundJson.success){
                            alert("Data inserted and latest round updated successfully!");
                        } else {
                            alert("Data inserted, but failed to update latest round: " + (roundJson.message||"Unknown error"));
                        }
                    } catch(err){
                        console.error("Round update error:", err);
                        alert("Data inserted, but error updating latest round.");
                    }

                    // Close modal & reload to reset form
                    modal.classList.add("hidden");
                    window.location.reload();

                } else {
                    alert("Failed to insert data: " + (json.message || "Unknown error"));
                }
            } catch (err) {
                console.error("Insert error:", err);
                alert("Error inserting data.");
            }
        });

    };
});
