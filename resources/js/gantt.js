/**
 * Gantt Şeması için JavaScript Kodu
 */

// Gantt şemasını başlat
function initGantt(tasks, elementId, viewMode = 'Day', onClick = null) {
    const element = document.getElementById(elementId);
    
    if (!element) {
        console.error(`Element with id "${elementId}" not found`);
        return;
    }
    
    if (!tasks || tasks.length === 0) {
        element.innerHTML = '<div class="p-4 text-center text-gray-500">No tasks found in the selected date range.</div>';
        return;
    }
    
    // Mevcut Gantt şemasını temizle
    element.innerHTML = '';
    
    // Yeni Gantt şeması oluştur
    const gantt = new Gantt(element, tasks, {
        header_height: 50,
        column_width: 30,
        step: 24,
        view_mode: viewMode,
        bar_height: 20,
        bar_corner_radius: 3,
        arrow_curve: 5,
        padding: 18,
        date_format: 'YYYY-MM-DD',
        custom_popup_html: function(task) {
            // Özel popup HTML'i
            return `
                <div class="gantt-popup">
                    <h4>${task.name}</h4>
                    <p><strong>Start:</strong> ${task.start}</p>
                    <p><strong>End:</strong> ${task.end}</p>
                    <p><strong>Progress:</strong> ${task.progress}%</p>
                    ${task.assignee ? `<p><strong>Assignee:</strong> ${task.assignee}</p>` : ''}
                    ${task.description ? `<p><strong>Description:</strong> ${task.description}</p>` : ''}
                </div>
            `;
        },
        on_click: onClick || function(task) {
            // Varsayılan tıklama işlevi
            console.log('Task clicked:', task);
        },
        on_date_change: function(task, start, end) {
            // Tarih değişikliği olduğunda
            console.log('Task date changed:', task, start, end);
            
            // Livewire bileşenine tarih değişikliğini bildir
            if (window.Livewire) {
                window.Livewire.dispatch('taskDateChanged', {
                    taskId: task.id,
                    start: start,
                    end: end
                });
            }
        },
        on_progress_change: function(task, progress) {
            // İlerleme değişikliği olduğunda
            console.log('Task progress changed:', task, progress);
            
            // Livewire bileşenine ilerleme değişikliğini bildir
            if (window.Livewire) {
                window.Livewire.dispatch('taskProgressChanged', {
                    taskId: task.id,
                    progress: progress
                });
            }
        }
    });
    
    // Gantt nesnesini döndür
    return gantt;
}

// Görünüm modunu değiştir
function changeViewMode(gantt, viewMode) {
    if (gantt) {
        gantt.change_view_mode(viewMode);
    }
}

// Gantt şemasını belirli bir tarihe kaydır
function scrollToDate(gantt, date) {
    if (gantt) {
        gantt.scroll_to(date);
    }
}

// Gantt şemasını bugüne kaydır
function scrollToToday(gantt) {
    if (gantt) {
        const today = new Date();
        const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        gantt.scroll_to(formattedDate);
    }
}

// Gantt şemasını belirli bir göreve kaydır
function scrollToTask(gantt, taskId) {
    if (gantt && gantt.tasks) {
        const task = gantt.tasks.find(t => t.id === taskId);
        if (task) {
            gantt.scroll_to(task.start);
        }
    }
}

// Gantt şemasını dışa aktar (PNG olarak)
function exportGanttAsPNG(ganttElementId, filename = 'gantt-chart') {
    const element = document.getElementById(ganttElementId);
    if (!element) return;
    
    // html2canvas kütüphanesini kullan (eğer yüklüyse)
    if (window.html2canvas) {
        html2canvas(element).then(canvas => {
            const link = document.createElement('a');
            link.download = `${filename}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    } else {
        console.error('html2canvas kütüphanesi yüklü değil');
    }
}

// Dışa aktarma işlevleri
const GanttExporter = {
    // JSON olarak dışa aktar
    toJSON: function(tasks) {
        return JSON.stringify(tasks, null, 2);
    },
    
    // CSV olarak dışa aktar
    toCSV: function(tasks) {
        if (!tasks || tasks.length === 0) return '';
        
        // CSV başlıkları
        const headers = ['ID', 'Name', 'Start', 'End', 'Progress', 'Dependencies'];
        
        // CSV satırları
        const rows = tasks.map(task => [
            task.id,
            `"${task.name.replace(/"/g, '""')}"`, // Çift tırnak içindeki çift tırnakları kaçır
            task.start,
            task.end,
            task.progress,
            task.dependencies || ''
        ]);
        
        // Başlıkları ve satırları birleştir
        return [
            headers.join(','),
            ...rows.map(row => row.join(','))
        ].join('\n');
    }
};

// Global nesneleri dışa aktar
window.GanttChart = {
    init: initGantt,
    changeViewMode: changeViewMode,
    scrollToDate: scrollToDate,
    scrollToToday: scrollToToday,
    scrollToTask: scrollToTask,
    exportAsPNG: exportGanttAsPNG,
    exporter: GanttExporter
};
