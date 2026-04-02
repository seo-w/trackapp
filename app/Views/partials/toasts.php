<?php
/**
 * partial/toasts.php
 * Sistema de notificaciones globales impulsado por Alpine.js
 * Escucha el evento personalizado: dispatch-toast
 */
?>
<div 
    x-data="{ 
        notifications: [], 
        addToast(e) {
            const id = Date.now();
            this.notifications.push({
                id,
                message: e.detail.message,
                type: e.detail.type || 'info', // 'success', 'warning', 'danger', 'info'
                show: false
            });
            setTimeout(() => {
                const toast = this.notifications.find(n => n.id === id);
                if (toast) toast.show = true;
            }, 10);
            setTimeout(() => {
                this.removeToast(id);
            }, 3000);
        },
        removeToast(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index !== -1) {
                this.notifications[index].show = false;
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 400);
            }
        }
    }" 
    @dispatch-toast.window="addToast($event)" 
    class="position-fixed bottom-0 end-0 p-4" 
    style="z-index: 9999; pointer-events: none;"
>
    <template x-for="n in notifications" :key="n.id">
        <div 
            x-show="n.show"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-4 opacity-0 scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300 transform"
            x-transition:leave-start="translate-y-0 opacity-100 scale-100"
            x-transition:leave-end="translate-y-4 opacity-0 scale-95"
            class="toast-item mb-2 shadow-lg border-0 d-flex align-items-center px-4 py-3 text-white rounded-pill overflow-hidden"
            :class="{
                'bg-dark bg-opacity-75': n.type === 'info',
                'bg-success bg-opacity-75': n.type === 'success',
                'bg-warning bg-opacity-75 text-dark': n.type === 'warning',
                'bg-danger bg-opacity-75': n.type === 'danger'
            }"
            style="pointer-events: auto; backdrop-filter: blur(8px); min-width: 250px;"
        >
            <div class="me-3">
                <template x-if="n.type === 'success'"><i class="bi bi-check-circle-fill"></i></template>
                <template x-if="n.type === 'info'"><i class="bi bi-info-circle-fill"></i></template>
                <template x-if="n.type === 'warning'"><i class="bi bi-exclamation-triangle-fill"></i></template>
                <template x-if="n.type === 'danger'"><i class="bi bi-x-circle-fill"></i></template>
            </div>
            <div class="small fw-semibold" x-text="n.message"></div>
            <button type="button" class="btn-close btn-close-white ms-auto small" style="transform: scale(0.7);" @click="removeToast(n.id)"></button>
        </div>
    </template>
</div>

<script>
/**
 * Despacha un toast desde cualquier script de la app.
 * @param {string} msg El mensaje a mostrar.
 * @param {string} type El tipo de toast ('success', 'info', 'warning', 'danger').
 */
window.notify = function(msg, type = 'info') {
    window.dispatchEvent(new CustomEvent('dispatch-toast', { detail: { message: msg, type: type } }));
};
</script>
