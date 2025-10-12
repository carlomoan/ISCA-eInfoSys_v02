window.fillConfirmation = function(dataObj){
    let html = '';

    function getValue(key){
        return dataObj[key] && dataObj[key].trim()!=='' ? dataObj[key] : 'NOT YET';
    }

    // ===== Header =====
    html += `
      <div class="form-header">
          <h2>ISCA Phase III</h2>
          <h3>Field Collection and Laboratory Data</h3>
          <div class="meta">
            <span>Date: <span id="cf_date"></span></span> |
            <span>Round: <span id="cf_round"></span></span>
          </div>
      </div>`;

    // ===== All Tables =====
    const sections = [
        {title:'Household Information', keys:[['Household Name','hhname'],['Household Code','hhcode'],['Cluster ID','clstid'],['Cluster Name','clstname'],['Collection Date','field_coll_date'],['Form Title','ento_fld_frm_title'],['Device ID','deviceid'],['Recorded by','fldrecname']]},
        {title:'Field Collection - Anopheles gambiae', keys:[['Male','male_ag'],['Female','female_ag'],['Fed','fed_ag'],['Unfed','unfed_ag'],['Gravid','gravid_ag'],['Semi-Gravid','semi_gravid_ag']]},
        {title:'Laboratory Results - Anopheles funestus', keys:[['Male','male_af'],['Female','female_af'],['Fed','fed_af'],['Unfed','unfed_af'],['Gravid','gravid_af'],['Semi-Gravid','semi_gravid_af']]},
        {title:'Other Species - OAN', keys:[['Male','male_oan'],['Female','female_oan'],['Fed','fed_oan'],['Unfed','unfed_oan'],['Gravid','gravid_oan'],['Semi-Gravid','semi_gravid_oan']]},
        {title:'Other Species - Culex', keys:[['Male','male_culex'],['Female','female_culex'],['Fed','fed_culex'],['Unfed','unfed_culex'],['Gravid','gravid_culex'],['Semi-Gravid','semi_gravid_culex']]},
        {title:'Other Culex', keys:[['Male','male_other_culex'],['Female','female_other_culex']]},
        {title:'Aedes', keys:[['Male','male_aedes'],['Female','female_aedes']]},
        {title:'Metadata', keys:[['Start','start'],['End','end'],['User Role','user_role'],['Instance ID','instanceID']]}
    ];

    sections.forEach(section=>{
        html += `<table class="info-table" style="font-family:Calibri;font-size:10px;border-collapse:collapse;width:100%;">
                    <tr style="background:#d9d9d9;"><th colspan="${section.keys.length}">${section.title}</th></tr>
                    <tr>`;
        section.keys.forEach(([label,key])=>{
            html += `<td style="border:1px solid #000;padding:4px;"><strong>${label}</strong></td>
                     <td style="border:1px solid #000;padding:4px;color:${getValue(key)==='NOT YET'?'red':'black'}">${getValue(key)}</td>`;
        });
        html += `</tr></table><br/>`;
    });

    // ===== Footer =====
    html += `<div style="margin-top:20px;font-size:10px;">
                <span>Printed by: ${dataObj.fldrecname || 'Unknown'}</span><br/>
                <span>Signature: ___________________________</span><br/>
                <span>Date: ${new Date().toLocaleDateString()}</span>
             </div>`;

    confirmationBody.innerHTML = html;
}



document.getElementById('export-pdf')?.addEventListener('click', async function (e) {
    e.preventDefault();
    e.stopPropagation();

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape', 'pt', 'a4');

    // ================= HEADER =================
    doc.setFontSize(14);
    doc.setFont("helvetica", "bold");
    doc.text("ISCA Phase III", doc.internal.pageSize.getWidth() / 2, 30, { align: "center" });

    doc.setFontSize(11);
    doc.setFont("helvetica", "normal");
    doc.text("Field Collection and Laboratory Data", doc.internal.pageSize.getWidth() / 2, 50, { align: "center" });

    doc.setFontSize(10);
    doc.text("Date: " + new Date().toLocaleDateString(), 40, 70);
    doc.text("Printed by: <?= htmlspecialchars($_SESSION['fullname'] ?? 'Unknown User') ?>", 40, 85);

    // ================= TABLE =================
    const table = document.querySelector("#confirmation-table"); // target your table ID
    if (!table) {
        alert("No table found to export!");
        return;
    }

    // convert HTML table to pdf
    doc.autoTable({
        html: "#confirmation-table",
        startY: 110,
        theme: "grid",
        styles: {
            fontSize: 8,
            cellPadding: 3,
            halign: "center",
            valign: "middle"
        },
        didParseCell: function (data) {
            // highlight N/A with red text
            if (data.cell.text[0] === "N/A") {
                data.cell.styles.textColor = [255, 0, 0];
                data.cell.styles.fontStyle = "bold";
            }
        }
    });

    // ================= FOOTER =================
    const pageHeight = doc.internal.pageSize.getHeight();
    doc.setFontSize(10);
    doc.text("Signature: ___________________________", 40, pageHeight - 40);

    // ================= SAVE =================
    doc.save("ISCA_Field_Lab_Data.pdf");
});

