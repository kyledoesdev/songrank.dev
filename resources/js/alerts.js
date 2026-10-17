import Swal from 'sweetalert2';

export default class LivewireSweetAlert {
    constructor() {
        this.flash_styles = {
            'confirm-btn': 'btn-primary m-2 p-2',
            'destructive-btn': 'btn-danger m-2 p-2',
            'cancel-btn': 'btn-secondary text-zinc-800 m-2 p-2',
            'redirect-btn': 'btn-primary m-2 p-2',
        };

        window.Swal = Swal;
        window.confirm = this.confirm.bind(this);
        window.flash = this.flash.bind(this);
        window.flashSupport = this.flashSupport.bind(this);
    }

    async confirm({ title, message, confirmText, componentId = null, entityId = null, action = null, destructive = false }) {
        const result = await this.swal({
            ...this.flash_styles,
            'confirm-btn': this.flash_styles[destructive ? 'destructive-btn' : 'confirm-btn'],
        }).fire({
            title: title,
            text: message,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancel',
            reverseButtons: true
        });
    
        if (result.isConfirmed && action) {
            const component = componentId 
                ? Livewire.find(componentId)
                : Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            
            if (entityId != null) {
                await component.call(action, entityId);
            } else {
                await component.call(action);
            }
        }
    
        return result.isConfirmed;
    }

    flash({ title, message, submessage = null, icon = 'success' }) {
        const html = [
            message ? `<p>${message}</p>` : '',
            submessage ? `<p class="text-sm text-gray-500 mt-2 whitespace-pre-line">${submessage}</p>` : '',
        ].filter(Boolean).join('');

        return this.swal().fire({
            title,
            html,
            icon
        });
    }

    flashWithRedirect({title, icon = 'success', redirectLink, buttonText = 'View here!'}) {
        return this.swal().fire({
            title: title,
            icon: icon,
            html: '<a class="' + this.flash_styles['redirect-btn'] + '" href="' + redirectLink + '">' + buttonText + '</a>',
            showConfirmButton: false
        });
    }

    flashSupport({title, message, icon = 'info'}) {
        return this.swal().fire({
            title: title,
            icon: icon,
            html: `
                <p>${message}</p>
                <div class="flex justify-center items-center gap-2 mt-4">
                    <a class="${this.flash_styles['redirect-btn']}" href="/support">Support Song Rank</a>
                    <button class="${this.flash_styles['cancel-btn']}" onclick="Swal.close()">Dismiss</button>
                </div>
            `,
            showConfirmButton: false,
            width: '32em',
        });
    }

    swal(customStyles = null) {
        const styles = customStyles || this.flash_styles;
        return Swal.mixin({
            customClass: {
                confirmButton: styles['confirm-btn'],
                cancelButton: styles['cancel-btn'],
                htmlContainer: 'max-h-[500px] overflow-y-auto',
            },
            buttonsStyling: false
        });
    }
}