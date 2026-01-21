// Payments page logic: fetch payments and record new payments
(function() {
  function formatCurrency(amount) {
    const num = Number(amount) || 0;
    return `₱${num.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
  }

  function generateTransactionId() {
    const randomNum = Math.floor(10000 + Math.random() * 90000);
    const tx = '#TXN' + randomNum;
    const el = document.getElementById('transactionId');
    if (el) el.value = tx;
    return tx;
  }

  function setCurrentDateTime() {
    const now = new Date();
    const date = now.toISOString().split('T')[0];
    const time = now.toTimeString().split(' ')[0].substring(0, 5);
    const dEl = document.getElementById('paymentDate');
    const tEl = document.getElementById('paymentTime');
    if (dEl) dEl.value = date;
    if (tEl) tEl.value = time;
  }

  let amountTouched = false;

  function openModal() {
    const m = document.getElementById('paymentModal');
    if (!m) return;
    m.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    const m = document.getElementById('paymentModal');
    if (!m) return;
    m.classList.remove('active');
    document.body.style.overflow = 'auto';
    const f = document.getElementById('paymentForm');
    if (f) f.reset();
    generateTransactionId();
    setCurrentDateTime();
  }
  window.openModal = openModal;
  window.closeModal = closeModal;

  // Close modal when clicking outside
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('paymentModal');
    if (modal) {
      modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
      });
    }
  });

  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
  });

  function getFilters() {
    const status = document.getElementById('statusFilter')?.value || '';
    const method = document.getElementById('methodFilter')?.value || '';
    const range = document.getElementById('dateRangeFilter')?.value || '';
    const sort = document.getElementById('sortFilter')?.value || '';
    return { status, method, range, sort };
  }

  async function fetchPayments() {
    try {
      const f = getFilters();
      const qs = new URLSearchParams();
      if (f.status) qs.set('status', f.status);
      if (f.method) qs.set('method', f.method);
      if (f.range) qs.set('range', f.range);
      if (f.sort) qs.set('sort', f.sort);
      const url = qs.toString() ? ('get_payments.php?' + qs.toString()) : 'get_payments.php';
      const res = await fetch(url, { credentials: 'include' });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load payments');
      renderSummary(data.summary || {});
      renderMethodSums(data.methodSums || {});
      renderPayments(data.payments || []);
      renderActivity(data.activity || []);
      renderUnpaidRentals(data.unpaidRentals || []);
    } catch (err) {
      console.error('Error loading payments:', err);
    }
  }
  function renderUnpaidRentals(items) {
    const tbody = document.getElementById('unpaidRentalsTbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#7f8c8d;">No unpaid rentals found</td></tr>';
      return;
    }
    items.forEach(r => {
      const tr = document.createElement('tr');
      const startStr = [r.pickupDate || '', r.pickupTime || ''].filter(Boolean).join(' ');
      const status = (r.status || '').toLowerCase();
      tr.innerHTML = `
        <td><span class="rental-id">#${escapeHtml(String(r.rentalId))}</span></td>
        <td>${escapeHtml(r.customerName || '')}</td>
        <td>${escapeHtml(r.bikeModel || '')}</td>
        <td>${escapeHtml(startStr)}</td>
        <td><span class="status-badge ${statusClass(status)}">${escapeHtml(titleCase(status || ''))}</span></td>
        <td>
          <button class="action-btn record" title="Record Payment" data-rental-id="${escapeHtml(String(r.rentalId))}">
            <span class="icon"><img src="wallet-arrow.png"></span>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
    // quick action: prefill modal with rental id
    tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('.action-btn.record');
      if (btn) {
        const rid = btn.getAttribute('data-rental-id') || '';
        const ridEl = document.getElementById('rentalId');
        if (ridEl) ridEl.value = '#' + rid;
        openModal();
        updateExpectedAmount();
        updateCustomerFromRentalId();
      }
    }, { once: true });
  }

  async function updateExpectedAmount() {
    try {
      const rentalIdRaw = (document.getElementById('rentalId')?.value || '').trim();
      const rentalId = parseInt(rentalIdRaw.replace(/[^0-9]/g, ''), 10);
      if (!rentalId || isNaN(rentalId)) {
        const h = document.getElementById('amountHint');
        if (h) h.textContent = '';
        return;
      }
      const paymentDate = document.getElementById('paymentDate')?.value || '';
      const paymentTime = document.getElementById('paymentTime')?.value || '';
      const res = await fetch('compute_expected_amount.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ rentalId, paymentDate, paymentTime })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to compute');
      const hint = document.getElementById('amountHint');
      if (hint) hint.textContent = `Suggested: ${formatCurrency(data.expected)} (${data.hours} hr @ ₱${(Number(data.rate)||0).toFixed(2)}/hr)`;
      if (!amountTouched) {
        const amt = document.getElementById('amount');
        if (amt) amt.value = (Number(data.expected) || 0).toFixed(2);
      }
    } catch (err) {
      const hint = document.getElementById('amountHint');
      if (hint) hint.textContent = '';
    }
  }

  async function updateCustomerFromRentalId() {
    try {
      const rentalIdRaw = (document.getElementById('rentalId')?.value || '').trim();
      const rentalId = parseInt(rentalIdRaw.replace(/[^0-9]/g, ''), 10);
      const field = document.getElementById('customerName');
      const hint = document.getElementById('customerHint');
      if (!field) return;
      if (!rentalId || isNaN(rentalId)) { field.value = ''; if (hint) hint.textContent = ''; return; }
      if (hint) hint.textContent = 'Looking up customer...';
      const res = await fetch('get_rental_info.php?rentalId=' + encodeURIComponent(String(rentalId)), { credentials: 'include', cache: 'no-store' });
      const data = await res.json();
      if (!data.success) { field.value = ''; if (hint) hint.textContent = 'Rental not found.'; return; }
      field.value = data.customerName || '';
      if (hint) hint.textContent = '';
    } catch (e) {
      const field = document.getElementById('customerName');
      const hint = document.getElementById('customerHint');
      if (field) field.value = '';
      if (hint) hint.textContent = 'Unable to fetch customer.';
    }
  }

  function renderSummary(sum) {
    const today = document.getElementById('todayRevenue');
    const week = document.getElementById('weekRevenue');
    const month = document.getElementById('monthRevenue');
    const pendingCount = document.getElementById('pendingCount');
    const pendingTotal = document.getElementById('pendingTotal');
    if (today) today.textContent = formatCurrency(sum.todayRevenue || 0);
    if (week) week.textContent = formatCurrency(sum.weekRevenue || 0);
    if (month) month.textContent = formatCurrency(sum.monthRevenue || 0);
    if (pendingCount) pendingCount.textContent = String(sum.pendingCount || 0);
    if (pendingTotal) pendingTotal.textContent = `${formatCurrency(sum.pendingTotal || 0)} total`;

    function fmtPct(pct, suffix) {
      if (pct == null || isNaN(pct)) return '';
      const sign = pct > 0 ? '+' : '';
      return `${sign}${Math.round(pct)}%${suffix ? ' ' + suffix : ''}`;
    }
    const tTrend = document.getElementById('todayTrend');
    const wTrend = document.getElementById('weekTrend');
    const mTrend = document.getElementById('monthTrend');
    if (tTrend) tTrend.textContent = fmtPct(sum.todayChangePct, 'from yesterday');
    if (wTrend) wTrend.textContent = fmtPct(sum.weekChangePct);
    if (mTrend) mTrend.textContent = fmtPct(sum.monthChangePct);
  }

  function renderMethodSums(ms) {
    const cash = document.getElementById('cashAmount');
    const card = document.getElementById('cardAmount');
    const ewallet = document.getElementById('ewalletAmount');
    if (cash) cash.textContent = formatCurrency(ms.cash || 0);
    if (card) card.textContent = formatCurrency(ms.card || 0);
    if (ewallet) ewallet.textContent = formatCurrency(ms.ewallet || 0);

    // Percentages
    const total = (ms.cash || 0) + (ms.card || 0) + (ms.ewallet || 0) + (ms.bank || 0);
    function pct(v) { return total > 0 ? Math.round((v / total) * 100) + '%' : ''; }
    const pcCash = document.getElementById('cashPercent');
    const pcCard = document.getElementById('cardPercent');
    const pcEwallet = document.getElementById('ewalletPercent');
    if (pcCash) pcCash.textContent = pct(ms.cash || 0);
    if (pcCard) pcCard.textContent = pct(ms.card || 0);
    if (pcEwallet) pcEwallet.textContent = pct(ms.ewallet || 0);
  }

  function renderPayments(items) {
    const tbody = document.getElementById('paymentsTbody');
    if (!tbody) return;
    tbody.innerHTML = '';
      items.forEach(p => {
      const tr = document.createElement('tr');
      if ((p.status || '').toLowerCase() === 'pending') tr.classList.add('pending-row');
      tr.innerHTML = `
        <td><input type="checkbox"></td>
        <td><span class="transaction-id">${escapeHtml(p.transactionId || '')}</span></td>
        <td>
          <div class="datetime-info">
            <span class="date">${escapeHtml(p.dateStr || '')}</span>
            <span class="time">${escapeHtml(p.timeStr || '')}</span>
          </div>
        </td>
        <td><span class="rental-link">${escapeHtml(p.rentalId ? '#' + p.rentalId : '')}</span></td>
        <td>
          <div class="customer-info">
            <div class="customer-avatar">${initials(p.customerName || '')}</div>
            <span class="customer-name">${escapeHtml(p.customerName || '')}</span>
          </div>
        </td>
        <td><div class="payment-method"><span>${escapeHtml(titleCase(p.paymentMethod || ''))}</span></div></td>
        <td><span class="amount">${formatCurrency(p.amount || 0)}</span></td>
        <td><span class="status-badge ${statusClass(p.status)}">${escapeHtml(titleCase(p.status || ''))}</span></td>
        <td>
          <div class="action-buttons">
            <button class="action-btn view" title="View Details"><span class="icon"><img src="view.png"></span></button>
            ${p.status === 'pending' ? '<button class="action-btn confirm" title="Confirm Payment"><span class="icon"><img src="check (1).png"></span></button>' : ''}
          </div>
        </td>`;
        // Attach identifiers for actions
        tr.dataset.paymentId = p.id != null ? String(p.id) : '';
        tr.dataset.transactionId = p.transactionId != null ? String(p.transactionId) : '';
      tbody.appendChild(tr);
    });
  }

  function renderActivity(items) {
    const list = document.getElementById('activityList');
    if (!list) return;
    list.innerHTML = '';
    items.forEach(a => {
      const div = document.createElement('div');
      const dotClass = (a.status || '').toLowerCase() === 'completed' ? 'completed' : 'pending';
      div.className = 'activity-item';
      div.innerHTML = `
        <div class="activity-dot ${dotClass}"></div>
        <div class="activity-info">
          <span class="activity-text">${escapeHtml(a.text || '')}</span>
          <span class="activity-time">${escapeHtml(a.time || '')}</span>
        </div>
        <span class="activity-amount">${formatCurrency(a.amount || 0)}</span>
      `;
      list.appendChild(div);
    });
  }

  function statusClass(s) {
    const v = (s || '').toLowerCase();
    if (v === 'completed') return 'completed';
    if (v === 'pending') return 'pending';
    if (v === 'failed') return 'failed';
    return '';
  }

  function titleCase(s) { return (s || '').replace(/(^|\s)\w/g, m => m.toUpperCase()); }
  function initials(name) {
    const parts = (name || '').trim().split(/\s+/);
    return parts.slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || 'CU';
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    const rentalIdRaw = document.getElementById('rentalId').value.trim();
    const rentalId = parseInt(rentalIdRaw.replace(/[^0-9]/g, ''), 10);
    const payload = {
      transactionId: document.getElementById('transactionId').value,
      rentalId: rentalId,
      amount: parseFloat(document.getElementById('amount').value),
      paymentMethod: document.getElementById('paymentMethod').value,
      paymentDate: document.getElementById('paymentDate').value,
      paymentTime: document.getElementById('paymentTime').value,
      status: document.getElementById('status').value,
      notes: document.getElementById('notes').value
    };

    try {
      const res = await fetch('record_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to record payment');
      alert('Payment recorded successfully!');
      closeModal();
      await fetchPayments();
    } catch (err) {
      console.error('Record payment error:', err);
      alert('Failed to record payment: ' + (err.message || 'Unknown error'));
    }
  }
  window.handleSubmit = handleSubmit;

  document.addEventListener('DOMContentLoaded', function() {
    generateTransactionId();
    setCurrentDateTime();
    fetchPayments();
    // filter listeners
    ['statusFilter','methodFilter','dateRangeFilter','sortFilter'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('change', fetchPayments);
    });
    // expected amount listeners
    const amt = document.getElementById('amount');
    if (amt) {
      amt.addEventListener('input', () => { amountTouched = true; });
    }
    const rid = document.getElementById('rentalId');
    const pd = document.getElementById('paymentDate');
    const pt = document.getElementById('paymentTime');
    if (rid) rid.addEventListener('change', () => { updateExpectedAmount(); updateCustomerFromRentalId(); });
    if (pd) pd.addEventListener('change', updateExpectedAmount);
    if (pt) pt.addEventListener('change', updateExpectedAmount);
    // initial compute
    updateExpectedAmount();
    updateCustomerFromRentalId();
      // Delegate confirm button clicks
      const tbody = document.getElementById('paymentsTbody');
      if (tbody) {
        tbody.addEventListener('click', async (e) => {
          const btn = e.target.closest('.action-btn.confirm');
          if (btn) {
            const tr = e.target.closest('tr');
            const idStr = tr?.dataset?.paymentId || '';
            const id = parseInt(idStr, 10);
            if (!id || isNaN(id)) {
              alert('Unable to determine payment ID.');
              return;
            }
            try {
              await confirmPayment(id);
              alert('Payment confirmed.');
              fetchPayments();
            } catch (err) {
              console.error('Confirm payment error:', err);
              alert('Failed to confirm payment: ' + (err.message || 'Unknown error'));
            }
          }
        });
      }
  });
  async function confirmPayment(paymentId) {
    const res = await fetch('confirm_payment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ id: paymentId })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Confirmation failed');
    return true;
  }
})();
