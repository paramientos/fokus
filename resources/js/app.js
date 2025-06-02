import './bootstrap';

// Frappe Gantt kütüphanesini import et
import 'frappe-gantt';

// SortableJS kütüphanesini import et (sürükle-bırak için)
import Sortable from 'sortablejs';
window.Sortable = Sortable;

function startSortable() {
    console.log('sortable kontrol');

    let el = document.getElementById('sortable-statuses');
    if (!el || el.dataset.sortableInitialized === 'true') return;
    new Sortable(el, {
        animation: 150,
        ghostClass: 'bg-gray-100',
        onEnd: function (evt) {
            let items = [];
            let listItems = document.querySelectorAll('#sortable-statuses li');
            listItems.forEach((item, index) => {
                items.push({
                    value: item.getAttribute('data-id'),
                    order: index
                });
            });
            function findWireId(el) {
    while (el) {
        if (el.hasAttribute && el.hasAttribute('wire:id')) {
            return el.getAttribute('wire:id');
        }
        el = el.parentElement;
    }
    return null;
}

let elRoot = document.getElementById('status-manager-root');
let componentId = findWireId(elRoot);
if (componentId) {
    window.Livewire.find(componentId).call('updateStatusOrder', items);
}
        }
    });
    el.dataset.sortableInitialized = 'true';
}

document.addEventListener('livewire:update', function () {
    setTimeout(startSortable, 100);
});

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(startSortable, 100);
});

const observer = new MutationObserver(() => {
    setTimeout(startSortable, 100);
});

observer.observe(document.body, { childList: true, subtree: true });

// Gantt şeması için özel kodları import et
import './gantt';
