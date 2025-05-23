<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metal & Çelik Dashboard</title>
    <style>
        .dashboard {
            background: #ffffff;
            color: #333;
            padding: 20px;
            box-sizing: border-box;
        }

        .dashboard .dashboard-header {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-in;
        }

        .dashboard .dashboard-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #1a73e8;
        }

        .dashboard .projects {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard .project-card {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.5s ease-in-out;
            border: 1px solid #e9ecef;
        }

        .dashboard .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .dashboard .project-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }

        .dashboard .members {
            display: flex;
            margin-bottom: 15px;
        }

        .dashboard .members img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: -10px;
            border: 2px solid #fff;
            transition: transform 0.3s ease;
        }

        .dashboard .members img:hover {
            transform: scale(1.1);
            z-index: 1;
        }

        .dashboard .status-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }

        .dashboard .status-bar div {
            height: 100%;
            transition: width 0.5s ease;
        }

        .dashboard .roadmap {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            animation: fadeIn 0.7s ease-in;
            border: 1px solid #e9ecef;
        }

        .dashboard .roadmap h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #1a73e8;
        }

        .dashboard .timeline {
            position: relative;
            padding-left: 50px;
        }

        .dashboard .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #1a73e8;
        }

        .dashboard .timeline-item {
            position: relative;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .dashboard .timeline-item:hover {
            background: #e9ecef;
        }

        .dashboard .timeline-item::before {
            content: '';
            position: absolute;
            left: -34px;
            top: 18px;
            width: 12px;
            height: 12px;
            background: #1a73e8;
            border-radius: 50%;
        }

        .dashboard .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard .chart-box {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.8s ease-in-out;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .dashboard .chart-box h3 {
            font-size: 18px;
            color: #1a73e8;
            margin: 0;
        }

        .dashboard .chart-box .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 14px;
            color: #666;
        }

        .dashboard .chart-box .stats div {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
        }

        .dashboard .chart-box .stats div span {
            font-weight: bold;
            color: #333;
        }

        .dashboard canvas {
            width: 100% !important;
            height: 200px !important;
            margin-top: 10px;
        }

        .dashboard .materials {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .dashboard .materials h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #1a73e8;
        }

        .dashboard .materials table {
            width: 100%;
            border-collapse: collapse;
        }

        .dashboard .materials th, .dashboard .materials td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .dashboard .materials th {
            background: #f8f9fa;
            color: #1a73e8;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .dashboard .projects {
                grid-template-columns: 1fr;
            }
            .dashboard .chart-box .stats {
                grid-template-columns: 1fr;
            }
        }

        .vehicles {
            margin-bottom: 30px; /* Alt boşluk ekler */
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <h1>Metal & Çelik Proje Dashboard</h1>
        </div>

        <div class="vehicles">
            <h2>Araç Verileri</h2>
            <table id="vehicle-data-table">
                <thead>
                    <tr>
                        <th>Plaka</th>
                        <th>Sürücü</th>
                        <th>Konum</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dinamik olarak doldurulacak -->
                </tbody>
            </table>
        </div>

        <!-- Projects -->
        <div class="projects" id="projects-container"></div>

        <!-- Roadmap -->
        <div class="roadmap">
            <h2>İş Planı</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <h3>Malzeme Temini</h3>
                    <p>1 Mart - 5 Mart 2025</p>
                </div>
                <div class="timeline-item">
                    <h3>Üretim ve Montaj</h3>
                    <p>6 Mart - 20 Mart 2025</p>
                </div>
                <div class="timeline-item">
                    <h3>Kontroller ve Teslim</h3>
                    <p>21 Mart - 30 Mart 2025</p>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts">
            <div class="chart-box">
                <h3>Proje İlerlemesi</h3>
                <div class="stats">
                    <div>Toplam Proje: <span>3</span></div>
                    <div>Ortalama İlerleme: <span>%63</span></div>
                    <div>En İleri Proje: <span>Kaynak İşi (%85)</span></div>
                    <div>Geciken Proje: <span>Yok</span></div>
                </div>
                <canvas id="progressChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Ekip Performansı</h3>
                <div class="stats">
                    <div>Toplam Ekip: <span>5</span></div>
                    <div>Ort. Tamamlanan İş: <span>18</span></div>
                    <div>En Verimli: <span>Fatma (25 İş)</span></div>
                    <div>Devam Eden İşler: <span>20</span></div>
                </div>
                <canvas id="performanceChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>İş Güvenliği İstatistikleri</h3>
                <div class="stats">
                    <div>Toplam Kaza: <span>2</span></div>
                    <div>Güvenlik Uygunluğu: <span>%95</span></div>
                    <div>Denetim Eksikleri: <span>3</span></div>
                    <div>Son Denetim: <span>5 Mart 2025</span></div>
                </div>
                <canvas id="safetyChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Ekipman Durumu</h3>
                <div class="stats">
                    <div>Toplam Ekipman: <span>4</span></div>
                    <div>Aktif Kullanım: <span>%80</span></div>
                    <div>Bakım Gereken: <span>1</span></div>
                    <div>Son Bakım: <span>1 Mart 2025</span></div>
                </div>
                <canvas id="equipmentChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Proje Maliyetleri</h3>
                <div class="stats">
                    <div>Toplam Bütçe: <span>750.000 TL</span></div>
                    <div>Harcanan: <span>450.000 TL</span></div>
                    <div>Kalan Bütçe: <span>300.000 TL</span></div>
                    <div>En Pahalı: <span>Kaynak İşi</span></div>
                </div>
                <canvas id="costChart"></canvas>
            </div>
        </div>

        

        <!-- Materials -->
        <div class="materials">
            <h2>Malzeme Durumu</h2>
            <table>
                <thead>
                    <tr>
                        <th>Malzeme</th>
                        <th>Kullanılan</th>
                        <th>Stokta Kalan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Çelik Levha</td>
                        <td>120 ton</td>
                        <td>80 ton</td>
                    </tr>
                    <tr>
                        <td>Profil Boru</td>
                        <td>300 m</td>
                        <td>150 m</td>
                    </tr>
                    <tr>
                        <td>Kaynak Teli</td>
                        <td>50 kg</td>
                        <td>20 kg</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- jQuery ve Chart.js Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            // Proje Verileri
            const projects = [
                {
                    name: "Platform İşi - Fabrika A",
                    members: [
                        "https://randomuser.me/api/portraits/men/10.jpg",
                        "https://randomuser.me/api/portraits/men/11.jpg",
                        "https://randomuser.me/api/portraits/women/12.jpg"
                    ],
                    dueDate: "15 Mart 2025",
                    progress: 60,
                    statusColor: "#1a73e8"
                },
                {
                    name: "Konveyör İşi - Depo B",
                    members: [
                        "https://randomuser.me/api/portraits/men/13.jpg",
                        "https://randomuser.me/api/portraits/women/14.jpg"
                    ],
                    dueDate: "30 Mart 2025",
                    progress: 45,
                    statusColor: "#e91e63"
                },
                {
                    name: "Kaynak İşi - Tesis C",
                    members: [
                        "https://randomuser.me/api/portraits/men/15.jpg",
                        "https://randomuser.me/api/portraits/men/16.jpg",
                        "https://randomuser.me/api/portraits/women/17.jpg"
                    ],
                    dueDate: "20 Nisan 2025",
                    progress: 85,
                    statusColor: "#28a745"
                }
            ];

            async function loadVehicleData() {
                try {
                    const response = await fetch('../controllers/get_vehicle_data.php');
                    const data = await response.json();
                    const tbody = document.querySelector("#vehicle-data-table tbody");
                    tbody.innerHTML = '';
            
                    if (!data.success || !Array.isArray(data.result)) {
                        tbody.innerHTML = '<tr><td colspan="4">Veri alınamadı</td></tr>';
                        return;
                    }
            
                    data.result.forEach(vehicle => {
                        const row = document.createElement('tr');
            
                        // Plaka
                        const plate = vehicle.plate ?? '-';
            
                        // Sürücü Adı ve Soyadı
                        const driverName = vehicle.driverFirstName && vehicle.driverLastName
                            ? `${vehicle.driverFirstName} ${vehicle.driverLastName}`
                            : '-';
            
                        // Konum
                        let location = `${vehicle.quarter ?? ''} ${vehicle.way ?? ''}, ${vehicle.town ?? ''} / ${vehicle.city ?? ''}`.trim();
            
                        // Eğer adres "75. Yıl OSB Mh. 6. Cd. Nil Yemek Sanayi , Odunpazarı, Eskişehir" ise "METALES MAKINE" yaz
                        if (
                            vehicle.quarter === "75. Yıl OSB Mh." &&
                            vehicle.way === "6. Cd.  Nil Yemek Sanayi " &&
                            vehicle.town === "Odunpazarı" &&
                            vehicle.city === "Eskişehir"
                        ) {
                            location = "METALES MAKINE";
                        }
            
                        // Durum
                        const state = vehicle.state === 'STATE_MOVING' ? 'HAREKET HALİNDE' : 'DURUYOR';
            
                        // Durum rengi
                        const stateColor = vehicle.state === 'STATE_MOVING' ? '#28a745' : '#dc3545'; // Açık yeşil: hareket halinde, kırmızı: duruyor
            
                        row.innerHTML = `
                            <td>${plate}</td>
                            <td>${driverName}</td>
                            <td>${location}</td>
                            <td style="color: ${stateColor}; font-weight: bold;">${state}</td>
                        `;
            
                        tbody.appendChild(row);
                    });
                } catch (error) {
                    console.error('Veri yükleme hatası:', error);
                    const tbody = document.querySelector("#vehicle-data-table tbody");
                    tbody.innerHTML = '<tr><td colspan="4">Veri yüklenirken bir hata oluştu</td></tr>';
                }
            }
            
            // Sayfa yüklendiğinde araç verilerini yükle
            loadVehicleData();

            // Araç verilerini her 10 saniyede bir güncelle
            setInterval(() => {
                loadVehicleData();
            }, 25000); // 10000 milisaniye = 10 saniye

            
            

            // Projeleri Dinamik Olarak Ekleme (jQuery ile)
            const $projectsContainer = $('#projects-container');
            projects.forEach(project => {
                const $card = $('<div>').addClass('project-card');
                $card.html(`
                    <h3>${project.name}</h3>
                    <div class="members">
                        ${project.members.map(img => `<img src="${img}" alt="Member">`).join('')}
                    </div>
                    <p>Taahhüt Tarihi: ${project.dueDate}</p>
                    <div class="status-bar">
                        <div style="width: ${project.progress}%; background: ${project.statusColor};"></div>
                    </div>
                `);
                $projectsContainer.append($card);
            });

            // Proje İlerlemesi Grafiği
            const progressCtx = document.getElementById('progressChart').getContext('2d');
            new Chart(progressCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Platform İşi', 'Konveyör İşi', 'Kaynak İşi', 'Kalan'],
                    datasets: [{
                        data: [60, 45, 85, 10],
                        backgroundColor: ['#1a73e8', '#e91e63', '#28a745', '#e9ecef'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#333' } }
                    }
                }
            });

            // Ekip Performansı Grafiği
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Ali', 'Fatma', 'Hasan', 'Zeynep', 'Murat'],
                    datasets: [
                        {
                            label: 'Tamamlanan İşler',
                            data: [18, 25, 12, 20, 15],
                            backgroundColor: '#1a73e8'
                        },
                        {
                            label: 'Devam Eden İşler',
                            data: [4, 2, 6, 3, 5],
                            backgroundColor: '#e91e63'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, ticks: { color: '#333' } },
                        x: { ticks: { color: '#333' } }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { color: '#333' } }
                    }
                }
            });

            // İş Güvenliği İstatistikleri Grafiği
            const safetyCtx = document.getElementById('safetyChart').getContext('2d');
            new Chart(safetyCtx, {
                type: 'pie',
                data: {
                    labels: ['Kazalar', 'Güvenlik Uygunluğu', 'Denetim Eksikleri'],
                    datasets: [{
                        data: [2, 95, 3],
                        backgroundColor: ['#dc3545', '#28a745', '#ffc107'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#333' } }
                    }
                }
            });

            // Ekipman Durumu Grafiği
            const equipmentCtx = document.getElementById('equipmentChart').getContext('2d');
            new Chart(equipmentCtx, {
                type: 'bar',
                data: {
                    labels: ['Kaynak Makinesi', 'Kesim Makinesi', 'Vinç', 'Matkap'],
                    datasets: [{
                        label: 'Kullanım Oranı (%)',
                        data: [90, 75, 85, 70],
                        backgroundColor: '#17a2b8'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, max: 100, ticks: { color: '#333' } },
                        x: { ticks: { color: '#333' } }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { color: '#333' } }
                    }
                }
            });

            // Proje Maliyetleri Grafiği
            const costCtx = document.getElementById('costChart').getContext('2d');
            new Chart(costCtx, {
                type: 'pie',
                data: {
                    labels: ['Platform İşi', 'Konveyör İşi', 'Kaynak İşi'],
                    datasets: [{
                        data: [200000, 150000, 300000],
                        backgroundColor: ['#1a73e8', '#e91e63', '#28a745'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#333' } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw.toLocaleString()} TL`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>