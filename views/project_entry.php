<?php
// Veritabanı bağlantısını başlat

session_start(); 
include("../includes/db_config.php");

// Kullanıcıların mühendis rolünü listelemek için sorgu
$query = "SELECT id, username FROM users WHERE role LIKE '%engineer%'";
$result = $baglanti->query($query);

// Proje numarasını otomatik oluşturmak için
$queryLastProject = "SELECT project_no FROM projects ORDER BY id DESC LIMIT 1"; // Son projeyi al
$resultLastProject = $baglanti->query($queryLastProject);

if ($resultLastProject->num_rows > 0) {
    $lastProject = $resultLastProject->fetch_assoc();
    // Son projeyi bulduktan sonra numarayı artır
    $lastProjectNo = $lastProject['project_no'];
    $lastNumber = substr($lastProjectNo, -4); // Son numarayı al (0001, 0002, vb.)
    $newProjectNo = 'MT_' . date('Y_m') . '_' . str_pad((int)$lastNumber + 1, 4, '0', STR_PAD_LEFT);
} else {
    // Eğer hiç proje yoksa, ilk proje numarasını oluştur
    $newProjectNo = 'MT_' . date('Y_m') . '_0001';
}

$projectDate = date('Y-m-d');  // Proje giriş tarihi (bugünün tarihi)

// Aktif projeleri çekmek için sorgu
$queryActiveProjects = "SELECT project_no, project_name, status FROM projects WHERE status != 'completed' ORDER BY project_no DESC";
$resultActiveProjects = $baglanti->query($queryActiveProjects);

// Workplace tablosundan verileri çekmek için sorgu
$queryWorkplaces = "SELECT id, name, location FROM workplace";
$resultWorkplaces = $baglanti->query($queryWorkplaces);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Girişi</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    /* Proje Girişi sayfasına özel stiller */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    background: #f4f6f9;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.container .form-container {
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #1a73e8;
}

.container .form-container h1 {
    font-size: 26px;
    color: #1a73e8;
    margin-bottom: 25px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .form-container form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.container .form-container label {
    font-size: 14px;
    color: #444;
    font-weight: 500;
    margin-bottom: 5px;
}

.container .form-container input[type="text"],
.container .form-container input[type="file"],
.container .form-container select,
.container .form-container textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fafafa;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.container .form-container input[type="text"]:focus,
.container .form-container input[type="file"]:focus,
.container .form-container select:focus,
.container .form-container textarea:focus {
    border-color: #1a73e8;
    outline: none;
}

.container .form-container input[type="checkbox"] {
    accent-color: #1a73e8;
    margin-right: 8px;
}

.container .form-container textarea {
    resize: vertical;
    min-height: 120px;
}

.container .form-container button {
    background: linear-gradient(135deg, #1a73e8, #0d47a1);
    color: #fff;
    padding: 14px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.container .form-container button:hover {
    background: linear-gradient(135deg, #1557b0, #0b3d91);
}

.container .active-projects-container {
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-right: 5px solid #28a745;
}

.container .active-projects-container h2 {
    font-size: 22px;
    color: #28a745;
    margin-bottom: 20px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.container .active-projects-container #activeProjectsList {
    max-height: 400px; /* Maksimum yükseklik */
    overflow-y: auto; /* Taşma durumunda kaydırma çubuğu */
    margin: 0;
    padding: 0;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #fff;
}

/* Tablo başlıkları */
.container .active-projects-container #activeProjectsList::before {
    content: "Proje No | Proje Adı | Durum";
    display: block;
    padding: 10px 15px;
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 13px;
    border-bottom: 1px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 1;
}

.container .active-projects-container #activeProjectsList li {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: 10px;
    padding: 15px;
    border-bottom: 1px solid #f1f3f5;
    font-size: 13px;
    color: #555;
    align-items: center;
    transition: background 0.2s ease;
}

.container .active-projects-container #activeProjectsList li:hover {
    background: #f1f3f5;
}

.container .active-projects-container #activeProjectsList li:last-child {
    border-bottom: none;
}

.container .active-projects-container #activeProjectsList li strong {
    color: #333;
    font-weight: 600;
}

/* Hakedis No alanının görünürlüğü için */
#hakedis_no_container {
    display: none; /* Varsayılan olarak gizli */
}

#hakedis_no_container.visible {
    display: block; /* Checkbox işaretlendiğinde görünür */
}

/* Responsive Tasarım */
@media (max-width: 900px) {
    .container {
        grid-template-columns: 1fr;
        padding: 15px;
    }

    .container .form-container,
    .container .active-projects-container {
        border-left: none;
        border-right: none;
        border-top: 5px solid #1a73e8; /* Form için */
    }

    .container .active-projects-container {
        border-top: 5px solid #28a745; /* Tablo için */
    }
}

@media (max-width: 480px) {
    .container .form-container,
    .container .active-projects-container {
        padding: 20px;
    }

    .container .form-container h1,
    .container .active-projects-container h2 {
        font-size: 20px;
    }

    .container .form-container label,
    .container .active-projects-container #activeProjectsList li {
        font-size: 12px;
    }

    .container .form-container input[type="text"],
    .container .form-container input[type="file"],
    .container .form-container select,
    .container .form-container textarea {
        padding: 10px;
    }

    .container .form-container button {
        padding: 12px;
        font-size: 14px;
    }

    .container .active-projects-container #activeProjectsList li {
        grid-template-columns: 1fr;
        gap: 5px;
        padding: 10px;
    }

    .container .active-projects-container #activeProjectsList::before {
        font-size: 12px;
        padding: 8px 10px;
    }
}
    </style>

</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Proje Girişi</h1>
            <form id="projectForm">
                <!-- Hakedisli mi? Checkbox -->
                <label for="hakedisli">Hakedisli mi?</label>
                <input type="checkbox" id="hakedisli" name="hakedisli" onchange="toggleHakedisNoField()">

                <!-- Hakedis No (Eğer Hakedisli ise gösterilecek) -->
                <div id="hakedis_no_container" style="display:none;">
                    <label for="hakedis_no">Hakedis No:</label>
                    <input type="text" id="hakedis_no" name="hakedis_no" placeholder="Hakedis numarasını girin">
                </div>

                <!-- Proje No (Otomatik) -->
                <label for="project_no">Proje No:</label>
                <input type="text" id="project_no" name="project_no" value="<?php echo $newProjectNo; ?>" pattern="MT_\d{4}_\d{2}_\d{4}" title="Format: MT_YYYY_MM_XXXX" required>

                <!-- Proje Adı -->
                <label for="project_name">Proje Adı:</label>
                <input type="text" id="project_name" name="project_name" placeholder="Proje adını girin" required>

                <!-- Proje Mühendisi (Listbox) -->
                <label for="muhendis_id">Proje Mühendisi:</label>
                <select id="muhendis_id" name="muhendis_id" required>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['username']; ?></option>
                    <?php } ?>
                </select>

                <!-- Karşı Yetkili -->
                <label for="karsi_yetkili">Karşı Yetkili:</label>
                <input type="text" id="karsi_yetkili" name="karsi_yetkili" placeholder="Karşı yetkiliyi girin" required>

                <!-- Proje Firması (Yazılabilir input list) -->
                <label for="workplace">Proje Firması:</label>
                <input list="workplaces" id="workplace" name="workplace" placeholder="Proje firmasını girin" required>
                <datalist id="workplaces">
                    <?php while ($row = $resultWorkplaces->fetch_assoc()) { ?>
                        <option value="<?php echo $row['name']; ?>" data-id="<?php echo $row['id']; ?>" data-location="<?php echo $row['location']; ?>"></option>
                    <?php } ?>
                </datalist>

                <!-- Proje Yeri -->
                <label for="location">Proje Yeri:</label>
                <input type="text" id="location" name="location" placeholder="Proje yerini girin" required>

                <!-- Proje Notları -->
                <label for="description">Proje Notları:</label>
                <textarea id="description" name="description" rows="4" cols="50" placeholder="Proje notlarını girin"></textarea>

                <!-- Proje Görselleri -->
                <label for="image_path">Proje Görselleri:</label>
                <input type="file" id="image_path" name="image_path[]" accept="image/*" multiple>

                <!-- Proje Durumu (Gizli) -->
                <input type="hidden" id="status" name="status" value="kesif_bekleniyor">

                <button type="submit">Projeyi Kaydet</button>
            </form>
        </div>
        
        <div class="active-projects-container">
            <h2>Son Projeler</h2>
            <ul id="activeProjectsList">
                <?php while ($project = $resultActiveProjects->fetch_assoc()) { ?>
                    <li>
                        <strong>Proje No:</strong> <?php echo $project['project_no']; ?><br>
                        <strong>Proje Adı:</strong> <?php echo $project['project_name']; ?><br>
                        <strong>Durum:</strong> <?php echo $project['status']; ?>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>  

    <script>
        // Hakedisli seçeneği aktifse Hakedis No alanını göster
        function toggleHakedisNoField() {
            var hakedisli = document.getElementById("hakedisli").checked;
            var hakedisNoContainer = document.getElementById("hakedis_no_container");
            if (hakedisli) {
                hakedisNoContainer.style.display = "block";
            } else {
                hakedisNoContainer.style.display = "none";
            }
        }

        // Proje Firması seçildiğinde Proje Yeri'ni otomatik doldur
        document.getElementById('workplace').addEventListener('input', function() {
            var selectedOption = document.querySelector('#workplaces option[value="' + this.value + '"]');
            if (selectedOption) {
                document.getElementById('location').value = selectedOption.getAttribute('data-location');
            } else {
                document.getElementById('location').value = '';
            }
        });

        // Formu AJAX ile gönder ve aktif projeleri güncelle
        document.getElementById('projectForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Formun normal submit işlemini engelle

            var formData = new FormData(this);

            fetch('../controllers/save_project.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
                updateActiveProjects(); // Aktif projeleri güncelle
            })
            .catch(error => {
                console.error('Hata:', error);
                showNotification('Bir hata oluştu.', 'error');
            });
        });

        // Aktif projeleri güncellemek için AJAX isteği
        function updateActiveProjects() {
            fetch('../controllers/get_active_projects.php')
            .then(response => response.json())
            .then(data => {
                var activeProjectsList = document.getElementById('activeProjectsList');
                activeProjectsList.innerHTML = ''; // Mevcut listeyi temizle

                data.forEach(function(project) {
                    var li = document.createElement('li');
                    li.innerHTML = '<strong>Proje No:</strong> ' + project.project_no + '<br>' +
                                   '<strong>Proje Adı:</strong> ' + project.project_name + '<br>' +
                                   '<strong>Durum:</strong> ' + project.status;
                    activeProjectsList.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Hata:', error);
                showNotification('Aktif projeler güncellenirken bir hata oluştu.', 'error');
            });
        }
    </script>
</body>
</html>