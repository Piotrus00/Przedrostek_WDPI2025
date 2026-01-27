const normalizeDateInput = (value) => {
  if (!value) return null;
  const hasTimezone = /Z$|[+-]\d{2}:\d{2}$/.test(value);
  const isoLike = value.includes('T') ? value : value.replace(' ', 'T');
  return hasTimezone ? isoLike : `${isoLike}Z`;
};

const formatDateTime = (value) => {
  if (!value) return '-';
  const normalized = normalizeDateInput(value);
  if (!normalized) return '-';
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString();
};

const formatMoney = (value) => {
  const amount = Number(value) || 0;
  return `${amount}$`;
};

const renderEmptyRow = (tbody, colSpan, text) => {
  tbody.innerHTML = '';
  const row = document.createElement('tr');
  const cell = document.createElement('td');
  cell.colSpan = colSpan;
  cell.className = 'table-empty';
  cell.textContent = text;
  row.appendChild(cell);
  tbody.appendChild(row);
};

const loadAdminData = async () => {
  const root = document.querySelector('.admin-page');
  if (!root) return;

  const csrfToken = root.dataset.csrf || '';
  const usersTable = document.getElementById('adminUsersTable');
  const statsTable = document.getElementById('loginStatsTable');

  if (!usersTable || !statsTable) return;

  try {
    const response = await fetch('/api/admin');
    const data = await response.json();

    if (!data || !data.success) {
      renderEmptyRow(usersTable.tBodies[0], 9, 'No data available');
      renderEmptyRow(statsTable.tBodies[0], 5, 'No data available');
      return;
    }

    const users = Array.isArray(data.users) ? data.users : [];
    const stats = Array.isArray(data.loginAttemptStats) ? data.loginAttemptStats : [];

    // Users table
    const usersBody = usersTable.tBodies[0];
    usersBody.innerHTML = '';
    if (!users.length) {
      renderEmptyRow(usersBody, 9, 'No users found');
    } else {
      users.forEach(user => {
        const isEnabled = Boolean(user.enabled);
        const statusLabel = isEnabled ? 'Enabled' : 'Disabled';
        const toggleLabel = isEnabled ? 'Disable' : 'Enable';
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${user.id}</td>
          <td>${user.email}</td>
          <td>${user.firstname}</td>
          <td>${user.lastname}</td>
          <td>${user.role}</td>
          <td>${formatMoney(user.balance)}</td>
          <td>${formatDateTime(user.created_at)}</td>
          <td>${statusLabel}</td>
          <td>
            <button class="admin-action-btn" data-user-id="${user.id}">Delete</button>
            <button class="admin-action-btn" data-toggle-id="${user.id}" data-enabled="${isEnabled}">${toggleLabel}</button>
          </td>
        `;
        usersBody.appendChild(row);
      });

      usersBody.querySelectorAll('button[data-user-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const userId = Number(btn.dataset.userId || 0);
          if (!userId) return;
          if (!confirm('Are you sure you want to delete this user?')) return;

          const deleteResponse = await fetch('/api/admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'delete-user',
              userId,
              csrf_token: csrfToken
            })
          });
          const deleteData = await deleteResponse.json();
          if (!deleteData || !deleteData.success) {
            alert(deleteData?.error || 'Failed to delete user');
            return;
          }
          loadAdminData();
        });
      });

      usersBody.querySelectorAll('button[data-toggle-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const userId = Number(btn.dataset.toggleId || 0);
          if (!userId) return;
          const currentEnabled = btn.dataset.enabled === 'true';
          const nextEnabled = !currentEnabled;
          const confirmText = nextEnabled
            ? 'Enable this account?'
            : 'Disable this account?';
          if (!confirm(confirmText)) return;

          const toggleResponse = await fetch('/api/admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'set-enabled',
              userId,
              enabled: nextEnabled,
              csrf_token: csrfToken
            })
          });
          const toggleData = await toggleResponse.json();
          if (!toggleData || !toggleData.success) {
            alert(toggleData?.error || 'Failed to update account');
            return;
          }
          loadAdminData();
        });
      });
    }

    // Login attempt stats table
    const statsBody = statsTable.tBodies[0];
    statsBody.innerHTML = '';
    if (!stats.length) {
      renderEmptyRow(statsBody, 5, 'No data available');
    } else {
      stats.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${item.email || '-'}</td>
          <td>${item.ip_address}</td>
          <td>${item.failed_last_hour ?? 0}</td>
          <td>${formatDateTime(item.last_attempt_at)}</td>
          <td>${formatDateTime(item.blocked_until)}</td>
        `;
        statsBody.appendChild(row);
      });
    }
  } catch (error) {
    renderEmptyRow(usersTable.tBodies[0], 9, 'Failed to load data');
    renderEmptyRow(statsTable.tBodies[0], 5, 'Failed to load data');
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', loadAdminData);
} else {
  loadAdminData();
}
