document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('tableSearch');
  if (search) {
    search.addEventListener('input', () => {
      const term = search.value.toLowerCase();
      document.querySelectorAll('.searchable-table tbody tr').forEach(row => {
        row.hidden = !row.textContent.toLowerCase().includes(term);
      });
    });
  }
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', event => {
      if (!confirm('Delete this student? Their attendance and grade records will also be deleted.')) {
        event.preventDefault();
      }
    });
  });
});
