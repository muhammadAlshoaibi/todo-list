// وظائف عامة
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل التواريخ في حقول التاريخ
    const dateInputs = document.querySelectorAll('input[type="date"], input[type="datetime-local"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            input.value = localISOTime;
        }
    });

    // عرض تواريخ الاستحقاق بشكل أفضل
    const dueDates = document.querySelectorAll('.task-due');
    dueDates.forEach(due => {
        const dateText = due.textContent.trim();
        if (dateText) {
            const date = new Date(dateText);
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            due.textContent = date.toLocaleDateString('ar-SA', options);
        }
    });

    // تحديث حالة الإشعارات عند النقر عليها
    const notifications = document.querySelectorAll('.notification-item.unread');
    notifications.forEach(notification => {
        notification.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            if (notificationId) {
                fetch('update_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: notificationId })
                });
            }
            this.classList.remove('unread');
        });
    });
});

// وظيفة لتحديث حالة المهمة
function updateTaskStatus(taskId, newStatus) {
    fetch('update_task_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            task_id: taskId,
            new_status: newStatus 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('حدث خطأ أثناء تحديث حالة المهمة');
        }
    });
}

// وظيفة لإظهار التنبيهات
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.prepend(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}