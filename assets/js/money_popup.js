/**
 * MoneyPopup
 * Native Bootstrap Modal implementation matching the site's UI structure perfectly.
 */
class MoneyPopup {
    constructor() {
        this.modalId = 'adminMoneyModal';
        this.initDOM();
    }

    initDOM() {
        // Only inject if it doesn't already exist
        if (document.getElementById(this.modalId)) return;

        const html = `
            <div class="modal fade" id="${this.modalId}" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1050;">
                <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 6px;">
                        <div class="modal-header" style="border-bottom: 1px solid #dee2e6; padding: 1rem 1.5rem;">
                            <h5 class="modal-title font-weight-bold text-dark" id="${this.modalId}Title" style="font-size: 16px;">+ CỘNG SỐ DƯ</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="padding: 1rem 1.5rem; margin: -1rem -1.5rem -1rem auto;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-dark" style="padding: 24px;">
                            
                            <div class="row align-items-center mb-3">
                                <div class="col-4 font-weight-bold text-dark">Amount:</div>
                                <div class="col-8">
                                    <input type="number" id="${this.modalId}Amount" class="form-control" placeholder="Nhập số tiền cần cộng">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-4 font-weight-bold text-dark pt-2">Lý do (nếu có):</div>
                                <div class="col-8">
                                    <textarea id="${this.modalId}Reason" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="text-center text-muted mt-4 mb-2">
                                Nhấn vào YES để thực hiện <span id="${this.modalId}Verb">cộng</span> <b class="text-danger" id="${this.modalId}Preview">0</b> vào <b>VÍ</b>
                            </div>
                            
                        </div>
                        <div class="modal-footer border-0 pb-3 pr-3 pt-0 justify-content-end">
                            <button type="button" class="btn text-white px-4 font-weight-bold" data-dismiss="modal" style="background-color: #e74c3c; border-color: #e74c3c; border-radius: 4px;">Đóng</button>
                            <button type="button" class="btn text-white px-4 font-weight-bold" id="${this.modalId}Submit" style="background-color: #20c997; border-color: #20c997; border-radius: 4px;">Yes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);

        this.modalEl = $('#' + this.modalId);
        this.titleEl = document.getElementById(this.modalId + 'Title');
        this.amountEl = document.getElementById(this.modalId + 'Amount');
        this.reasonEl = document.getElementById(this.modalId + 'Reason');
        this.verbEl = document.getElementById(this.modalId + 'Verb');
        this.previewEl = document.getElementById(this.modalId + 'Preview');
        this.submitBtn = document.getElementById(this.modalId + 'Submit');

        // Form logic
        this.amountEl.addEventListener('input', () => {
            const val = parseInt(this.amountEl.value) || 0;
            this.previewEl.textContent = val.toLocaleString('vi-VN');
        });

        this.submitBtn.addEventListener('click', () => this.submit());
    }

    show(opts) {
        this.opts = opts;
        this.verb = opts.verb || 'cộng';

        // Reset form
        this.titleEl.innerHTML = opts.title || '+ CỘNG SỐ DƯ';
        this.amountEl.value = '';
        this.amountEl.placeholder = 'Nhập số tiền cần ' + this.verb;
        this.reasonEl.value = '';
        this.verbEl.textContent = this.verb;
        this.previewEl.textContent = '0';

        // Optional custom button text/colors if needed, though the screenshot has fixed #20c997
        if (opts.confirmColor) {
            this.submitBtn.style.backgroundColor = opts.confirmColor;
            this.submitBtn.style.borderColor = opts.confirmColor;
        } else {
            this.submitBtn.style.backgroundColor = '#20c997'; // default teal
            this.submitBtn.style.borderColor = '#20c997';
        }

        this.modalEl.modal('show');
    }

    submit() {
        const amount = this.amountEl.value;
        const reason = this.reasonEl.value;

        if (!amount || parseInt(amount) <= 0) {
            if (typeof SwalHelper !== 'undefined') {
                SwalHelper.error('Vui lòng nhập số tiền hợp lệ (> 0)');
            } else {
                alert('Vui lòng nhập số tiền hợp lệ (> 0)');
            }
            return;
        }

        this.modalEl.modal('hide');
        if (typeof SwalHelper !== 'undefined') {
            SwalHelper.loading('Đang xử lý...');
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.opts.actionUrl;
        form.style.display = 'none';

        const amountInputHidden = document.createElement('input');
        amountInputHidden.type = 'hidden';
        amountInputHidden.name = this.opts.amountName || 'amount';
        amountInputHidden.value = amount;

        const reasonInputHidden = document.createElement('input');
        reasonInputHidden.type = 'hidden';
        reasonInputHidden.name = this.opts.reasonName || 'reason';
        reasonInputHidden.value = reason;

        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        if (metaCsrf && metaCsrf.content) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = metaCsrf.content;
            form.appendChild(csrfInput);
        } else if (window.KS_CSRF_TOKEN) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = window.KS_CSRF_TOKEN;
            form.appendChild(csrfInput);
        }

        form.appendChild(amountInputHidden);
        form.appendChild(reasonInputHidden);
        document.body.appendChild(form);
        form.submit();
    }
}

// Instantiate globally to be accessible everywhere
const adminMoneyPopup = new MoneyPopup();
