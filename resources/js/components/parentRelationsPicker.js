export default (parentsList = [], selectedIds = []) => ({
    search: '',
    currentPage: 1,
    perPage: 10,
    selectedIds: Array.isArray(selectedIds) ? selectedIds : [],
    parentsList: Array.isArray(parentsList) ? parentsList : [],

    get filteredIndices() {
        if (!this.search || !this.search.trim()) {
            return this.parentsList.map(function(p) { return p.index; });
        }
        var q = this.search.toLowerCase().trim();
        return this.parentsList
            .filter(function(p) {
                return (p.name && p.name.toLowerCase().indexOf(q) !== -1) || 
                       (p.phone && String(p.phone).toLowerCase().indexOf(q) !== -1);
            })
            .map(function(p) { return p.index; });
    },

    get totalPages() {
        return Math.ceil(this.filteredIndices.length / this.perPage) || 1;
    },

    get paginatedIndices() {
        var start = (this.currentPage - 1) * this.perPage;
        return this.filteredIndices.slice(start, start + this.perPage);
    },

    prevPage() {
        if (this.currentPage > 1) this.currentPage--;
    },

    nextPage() {
        if (this.currentPage < this.totalPages) this.currentPage++;
    },

    toggleSelect(id, isChecked) {
        if (isChecked && !this.selectedIds.includes(id)) {
            this.selectedIds.push(id);
        } else if (!isChecked) {
            this.selectedIds = this.selectedIds.filter(function(i) { return i !== id; });
        }
    }
});
