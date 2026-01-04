
document.addEventListener('DOMContentLoaded', function () {
    const prescriptionList = document.getElementById('prescriptionList');

    // Stats or filters could be added here later

    function fetchAndRenderPrescriptions() {
        if (!prescriptionList) return;

        prescriptionList.innerHTML = '<div class="loading-spinner">Loading...</div>';

        fetch('get_patient_prescriptions.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.prescriptions.length > 0) {
                    prescriptionList.innerHTML = '';

                    data.prescriptions.forEach(p => {
                        const date = new Date(p.created_at).toLocaleDateString(undefined, {
                            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
                        });

                        const card = document.createElement('div');
                        card.className = 'prescription-card';
                        card.style.cssText = `
                            background: white; 
                            padding: 24px; 
                            border-radius: 16px; 
                            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
                            border: 1px solid var(--border-color);
                            transition: transform 0.2s;
                            display: flex;
                            flex-direction: column;
                            gap: 16px;
                        `;

                        card.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <div style="background: var(--primary-light); color: var(--primary-color); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                        <i class="fas fa-file-prescription"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-dark);">Dr. ${p.doctor_name}</h5>
                                        <span style="font-size: 0.9rem; color: var(--text-muted);">${p.specialization}</span>
                                    </div>
                                </div>
                                <span class="badge" style="background: #f1f5f9; color: #64748b; padding: 6px 12px; border-radius: 20px; font-weight: 500;">
                                    <i class="far fa-calendar-alt"></i> ${date}
                                </span>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; font-size: 0.95rem; line-height: 1.6; color: #334155; border: 1px dashed #cbd5e1;">
                                ${p.content}
                            </div>
                            
                            <div style="margin-top: auto;">
                                <button class="btn btn-outline-primary w-100 download-rx" 
                                        style="border-radius: 10px; padding: 12px; font-weight: 600;"
                                        data-content="${encodeURIComponent(p.content)}" 
                                        data-doctor="${encodeURIComponent(p.doctor_name)}" 
                                        data-date="${date}">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
                            </div>
                        `;
                        prescriptionList.appendChild(card);
                    });

                    // Add listeners
                    document.querySelectorAll('.download-rx').forEach((btn, index) => {
                        btn.addEventListener('click', function () {
                            const p = data.prescriptions[index];
                            const date = this.dataset.date;
                            printPrescription(p, date);
                        });
                    });
                } else {
                    prescriptionList.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                            <div style="font-size: 4rem; color: #cbd5e1; margin-bottom: 20px;"><i class="fas fa-file-medical"></i></div>
                            <h3 style="color: #64748b;">No prescriptions found</h3>
                            <p style="color: #94a3b8;">Prescriptions from your doctor visits will appear here.</p>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error(err);
                prescriptionList.innerHTML = '<div class="alert alert-danger">Failed to load prescriptions.</div>';
            });
    }

    function printPrescription(p, date) {
        const content = p.content || '';
        const doctor = p.doctor_name || 'Doctor';
        let medicineRows = '';
        let notes = '';

        // Prioritize structured items if available
        if (p.items && p.items.length > 0) {
            p.items.forEach(item => {
                medicineRows += `
                    <tr>
                        <td><strong>${item.medicine_name}</strong></td>
                        <td>${item.dosage || '-'}</td>
                        <td>${item.frequency || '-'}</td>
                        <td>${item.duration || '-'}</td>
                    </tr>
                `;
            });
            // Treat the content as notes in this case, but filter out the parts that might be duplication
            // Actually, for consistency, if we have items, we just show content as notes.
            notes = content.split('\n').filter(line => !line.includes('|')).join('<br>');
        } else if (content.includes('|')) {
            // Fallback for older records without structured items
            const lines = content.split('\n');
            let hasTable = false;

            lines.forEach(line => {
                if (line.includes('|')) {
                    hasTable = true;
                    const parts = line.split('|').map(s => s.trim());
                    if (parts.length >= 1) {
                        medicineRows += `
                            <tr>
                                <td><strong>${parts[0]}</strong></td>
                                <td>${parts[1] || '-'}</td>
                                <td>${parts[2] || '-'}</td>
                                <td>${parts[3] || '-'}</td>
                            </tr>
                        `;
                    }
                } else {
                    if (line.trim()) {
                        notes += `<p>${line}</p>`;
                    }
                }
            });

            if (!hasTable) {
                notes = content.replace(/\n/g, '<br>');
            }
        } else {
            notes = content.replace(/\n/g, '<br>');
        }

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Prescription - ${date}</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
                        body { font-family: 'Inter', sans-serif; padding: 0; margin: 0; color: #1e293b; background: white; }
                        .container { max-width: 800px; margin: 0 auto; padding: 40px; }
                        
                        /* Header */
                        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; margin-bottom: 40px; }
                        .logo { font-size: 28px; font-weight: 800; color: #4f46e5; text-transform: uppercase; letter-spacing: -0.5px; }
                        .clinic-info { text-align: right; font-size: 0.9rem; color: #64748b; }
                        
                        /* Doctor/Patient Info */
                        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
                        .info-box h3 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin: 0 0 10px 0; }
                        .info-box p { font-size: 1.1rem; font-weight: 600; margin: 0; color: #334155; }
                        .info-box span { font-size: 0.95rem; font-weight: 400; color: #64748b; display: block; margin-top: 4px; }
                        
                        /* Rx Symbol */
                        .rx-symbol { font-size: 3rem; font-weight: 900; color: #cbd5e1; margin-bottom: 20px; font-family: serif; font-style: italic; }
                        
                        /* Medicine Table */
                        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                        th { text-align: left; padding: 12px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
                        td { padding: 16px 12px; border-bottom: 1px solid #f1f5f9; font-size: 1rem; color: #334155; vertical-align: top; }
                        td strong { color: #0f172a; font-weight: 600; }
                        
                        /* Notes */
                        .notes-section { background: #f8fafc; padding: 20px; border-radius: 12px; margin-top: 20px; border: 1px solid #f1f5f9; }
                        .notes-section h4 { margin: 0 0 10px 0; color: #475569; font-size: 0.9rem; }
                        .notes-content { line-height: 1.6; color: #475569; font-size: 0.95rem; }
                        
                        /* Footer */
                        .footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-end; }
                        .footer-text { font-size: 0.8rem; color: #94a3b8; max-width: 60%; }
                        
                        .signature-box { text-align: center; }
                        .signature-line { width: 200px; border-bottom: 1px solid #cbd5e1; margin-bottom: 8px; }
                        .signature-label { font-size: 0.85rem; color: #64748b; }

                        @media print {
                            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                            .notes-section { background-color: #f8fafc !important; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <div class="logo">HealthCare</div>
                            <div class="clinic-info">
                                <strong>Twincle Medical Center</strong><br>
                                123 Health Avenue, Medical District<br>
                                Contact: +1 (555) 123-4567
                            </div>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-box">
                                <h3>Doctor</h3>
                                <p>Dr. ${doctor}</p>
                                <span>General Specialist</span>
                            </div>
                            <div class="info-box">
                                <h3>Date</h3>
                                <p>${date}</p>
                                <span>Prescription ID: #${Math.floor(Math.random() * 10000)}</span>
                            </div>
                        </div>
                        
                        <div class="rx-symbol">Rx</div>
                        
                        ${medicineRows ? `
                        <table>
                            <thead>
                                <tr>
                                    <th width="40%">Medicine</th>
                                    <th width="20%">Dosage</th>
                                    <th width="20%">Frequency</th>
                                    <th width="20%">Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${medicineRows}
                            </tbody>
                        </table>
                        ` : ''}
                        
                        ${notes ? `
                        <div class="notes-section">
                            <h4>Special Instructions / Notes</h4>
                            <div class="notes-content">${notes}</div>
                        </div>
                        ` : ''}
                        
                        <div class="footer">
                            <div class="footer-text">
                                <p>This prescription is valid for 30 days from the date of issue unless otherwise specified. Please consult your doctor for any clarifications.</p>
                                <p>Electronically generated by HealthCare Management System.</p>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Doctor's Signature</div>
                            </div>
                        </div>
                    </div>
                    <script>
                        window.print();
                        window.onafterprint = function() { window.close(); }
                    </script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    fetchAndRenderPrescriptions();
});
