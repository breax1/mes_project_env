document.addEventListener("DOMContentLoaded", function () {
    const notificationIcon = document.getElementById('notification-icon');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationList = document.getElementById('notification-list');
    const notificationCount = document.getElementById('notification-count');

    // Dropdown'u aç/kapat
    notificationIcon.addEventListener('click', () => {
        notificationDropdown.style.display = notificationDropdown.style.display === 'none' ? 'block' : 'none';
    });

    let audio = new Audio("../assets/sounds/notification.mp3");
    audio.load(); // Ses dosyasını yükle

    // Bildirim izni iste
    if (Notification.permission !== "granted") {
        Notification.requestPermission().then(function(permission) {
            if (permission === "granted") {
                console.log("Bildirim izni verildi.");
            } else {
                console.log("Bildirim izni reddedildi.");
            }
        });
    }

    let initialLoad = true; // Sayfa ilk yüklendiğinde true olacak

    // Bildirimleri AJAX ile al
    function fetchNotifications() {
        fetch('../includes/get_notifications.php')
            .then((response) => response.json())
            .then((data) => {
                console.log(data); // Gelen veriyi kontrol etmek için konsola yazdırın
                
                // Eğer yeni bildirim varsa ses çal ve masaüstü bildirimi göster
                const newNotifications = data.unreadCount > parseInt(notificationCount.textContent);
                if (newNotifications && !initialLoad) {
                    audio.play().catch((e) => console.log("Ses oynatırken hata:", e));
                    const latestNotification = data.notifications
                        .filter(notification => !notification.is_read)
                        .pop(); // Son bildirimi al
                    if (latestNotification) {
                        showNotification(latestNotification.message, 'info');
                    }
                }

                notificationCount.textContent = data.unreadCount;

                // Bildirimleri listeye ekleyin (sadece okunmamış bildirimler)
                const unreadNotifications = data.notifications
                    .filter(notification => !notification.is_read) // Sadece okunmamış bildirimleri filtrele
                    .reverse(); // En yeni bildirimi en üste koymak için ters sırala

                if (unreadNotifications.length === 0) {
                    $('#notification-list').html('<li class="no-notifications">Henüz bildiriminiz yok</li>');
                } else {
                    $('#notification-list').html(unreadNotifications
                        .map((notification) => {
                            return `
                                <li class="unread">
                                    <p>${notification.message}</p>
                                    <small>${notification.created_at}</small>
                                    <button class="mark-as-read" data-id="${notification.id}">Okundu</button>
                                </li>`;
                        })
                        .join(""));
                }

                // "Okundu" butonlarına olay dinleyici ekleyin
                document.querySelectorAll('.mark-as-read').forEach(button => {
                    button.addEventListener('click', function () {
                        const notificationId = this.getAttribute('data-id');
                        markAsRead(notificationId);
                    });
                });

                initialLoad = false; // İlk yükleme tamamlandı
            })
            .catch((error) => console.error("Bildirim alınırken hata oluştu:", error))
            .finally(() => {
                // 5 saniye sonra tekrar kontrol et
                setTimeout(fetchNotifications, 5000);
            });
    }

    // Bildirimi okundu olarak işaretle
    function markAsRead(notificationId) {
        fetch('../includes/get_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                fetchNotifications(); // Bildirimleri güncelle
            } else {
                console.error('Bildirim okundu olarak işaretlenemedi:', data.message);
            }
        })
        .catch(error => {
            console.error('Hata:', error);
        });
    }

    // Sayfa yüklenince bildirimi bir kere kontrol et
    fetchNotifications();
});


document.addEventListener("DOMContentLoaded", () => {
    // Menü linklerini seç
    const menuLinks = document.querySelectorAll(".menu-link");
    const contentArea = document.getElementById("content-area");
    let currentPage = window.location.pathname;

    // Her bir linke tıklama olayını ekle
    menuLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault(); // Linkin varsayılan davranışını engelle

            // Data-page attribute'ünden hedef sayfayı al
            const page = link.getAttribute("data-page");

            // Eğer zaten aynı sayfadaysak, yeniden yükleme
            if (currentPage === page) {
                return;
            }

            // AJAX ile içeriği yükle
            fetch(page)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Sayfa yüklenemedi!");
                    }
                    return response.text();
                })
                .then((html) => {
                    contentArea.innerHTML = html; // İçeriği güncelle
                    executeScripts(contentArea); // Scriptleri çalıştır
                    currentPage = page; // Mevcut sayfayı güncelle
                })
                .catch((error) => {
                    contentArea.innerHTML = `<p style="color:red;">Hata: ${error.message}</p>`;
                });
        });
    });
});


// Sayfa yüklendiğinde dashboard.php'yi yükle
$(document).ready(function() {
    $('#content-area').load('views/dashboard.php');
});

// Menü öğesine tıklandığında aktif sınıfını ekle
document.querySelectorAll('.menu a').forEach(item => {
  item.addEventListener('click', function() {
      document.querySelectorAll('.sidebar a').forEach(link => {
          link.classList.remove('active'); // Diğerlerini aktif olmaktan çıkar
      });
      item.classList.add('active'); // Tıklananı aktif yap
  });
});




function executeScripts(element) {
  const scripts = element.querySelectorAll("script");
  scripts.forEach((script) => {
    const newScript = document.createElement("script");
    if (script.src) {
      newScript.src = script.src;
    } else {
      newScript.textContent = script.textContent;
    }
    document.head.appendChild(newScript).parentNode.removeChild(newScript);
  });
}

feather.replace()


function showNotification(message, type) {
    const container = document.getElementById('custom-notification-wrapper');

    // Yeni bildirim kutusunu oluştur
    const notificationBox = document.createElement('div');
    notificationBox.className = `notification-box ${type}`;
    
    // Bildirim mesajı ekle
    notificationBox.innerHTML = `
        <button class="close-btn">&times;</button>
        <div class="notification-message">${message}</div>
    `;

    // Bildirimi container'a ekle
    container.appendChild(notificationBox);

    // Görünür hale getir
    setTimeout(() => {
        notificationBox.classList.add('show');
    }, 10);

    // Çarpı butonu ile kapatma işlemi
    notificationBox.querySelector('.close-btn').addEventListener('click', () => removeNotification(notificationBox));

    // 5 saniye sonra otomatik sil
    setTimeout(() => removeNotification(notificationBox), 5000);
}

function removeNotification(notification) {
    notification.classList.add('hide'); // Kaybolma animasyonu ekle

    setTimeout(() => {
        notification.remove();
    }, 500); // CSS'teki geçiş süresiyle aynı olmalı
}


// Profil fotoğrafı ve açılır menü için olaylar
$(document).ready(function() {
    var timeout;
    $('.profile-img').hover(function() {
        clearTimeout(timeout);
        $('.profile-dropdown').show();
    }, function() {
        timeout = setTimeout(function() {
            $('.profile-dropdown').hide();
        }, 2000);
    });

    $('.profile-dropdown').hover(function() {
        clearTimeout(timeout);
    }, function() {
        timeout = setTimeout(function() {
            $('.profile-dropdown').hide();
        }, 2000);
    });

    // Bildirim simgesine tıklanırsa açılır menüyü hemen kapat
    $('#notification-icon').hover(function() {
        clearTimeout(timeout);
        $('.profile-dropdown').hide();
    });

    // Bildirim açılır menüsü için olaylar
    var notificationTimeout;
    $('#notification-icon').hover(function() {
        clearTimeout(notificationTimeout);
        $('#notification-dropdown').show();
    }, function() {
        notificationTimeout = setTimeout(function() {
            $('#notification-dropdown').hide();
        }, 2000);
    });

    $('#notification-dropdown').hover(function() {
        clearTimeout(notificationTimeout);
    }, function() {
        notificationTimeout = setTimeout(function() {
            $('#notification-dropdown').hide();
        }, 2000);
    });

    // Profil fotoğrafına tıklanırsa bildirim açılır menüsünü hemen kapat
    $('.profile-img').hover(function() {
        clearTimeout(notificationTimeout);
        $('#notification-dropdown').hide();
    });
});


