
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
                    document.querySelectorAll('.download-rx').forEach(btn => {
                        btn.addEventListener('click', function () {
                            const content = decodeURIComponent(this.dataset.content);
                            const doctor = decodeURIComponent(this.dataset.doctor);
                            const date = this.dataset.date;
                            printPrescription(content, doctor, date);
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

    function printPrescription(content, doctor, date) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Prescription - ${date}</title>
                    <style>
                        body { font-family: 'Segoe UI', sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; color: #333; }
                        .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #0f172a; padding-bottom: 20px; }
                        .header h1 { color: #0f172a; margin-bottom: 10px; }
                        .logo { font-size: 24px; font-weight: bold; color: #4f46e5; }
                        .meta { margin-bottom: 40px; display: flex; justify-content: space-between; background: #f8fafc; padding: 20px; border-radius: 8px; }
                        .content { white-space: pre-wrap; line-height: 1.8; font-size: 14pt; border: 1px solid #e2e8f0; padding: 30px; border-radius: 8px; min-height: 300px; }
                        .footer { margin-top: 50px; font-size: 0.9em; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; }
                        @media print {
                            body { -webkit-print-color-adjust: exact; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="logo">Twincle Healthcare</div>
                        <h1>Medical Prescription</h1>
                    </div>
                    <div class="meta">
                        <div>
                            <strong>Doctor:</strong> ${doctor}<br>
                            <strong>Specialist</strong>
                        </div>
                        <div>
                            <strong>Date:</strong> ${date}
                        </div>
                    </div>
                    <div class="content">${content}</div>
                    <div class="footer">
                        <p>This document is a valid medical prescription generated by Twincle Healthcare System.</p>
                        <p>Electronically Signed • ${new Date().toLocaleString()}</p>
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
