/* Recommendations product search (modal).
   Moved from backEnd/recommendations/index.blade.php; the search endpoint
   rides in on [data-search-url]. */
    (function () {
        var searchInput = document.getElementById('productSearchInput');
        var searchBtn = document.getElementById('searchProductBtn');
        var resultsDiv = document.getElementById('searchResults');
        var selectedIdInput = document.getElementById('selectedProductId');
        var selectedProductDisplay = document.getElementById('selectedProductDisplay');
        var selectedProductName = document.getElementById('selectedProductName');
        var addProductBtn = document.getElementById('addProductBtn');
        var searchUrl = document.querySelector('[data-search-url]').getAttribute('data-search-url');
        
        function performSearch() {
            var term = searchInput.value.trim();
            if (!term) return;
            
            fetch(searchUrl + '?q=' + encodeURIComponent(term))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    resultsDiv.innerHTML = '';
                    resultsDiv.style.display = 'block';
                    
                    if (data.results && data.results.length) {
                        data.results.forEach(function(item) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action py-2';
                            btn.innerHTML = '<div class="d-flex justify-content-between align-items-center">' +
                                '<span>' + item.text + '</span>' +
                                '<span class="badge bg-label-primary">ID: ' + item.id + '</span>' +
                                '</div>';
                            btn.onclick = function() {
                                searchInput.value = item.text;
                                selectedIdInput.value = item.id;
                                selectedProductName.textContent = item.text + ' (ID: ' + item.id + ')';
                                selectedProductDisplay.classList.remove('d-none');
                                addProductBtn.disabled = false;
                                resultsDiv.style.display = 'none';
                            };
                            resultsDiv.appendChild(btn);
                        });
                    } else {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted py-3 text-center"><i class="ri ri-inbox-line ri-lg me-2"></i>No products found</div>';
                    }
                })
                .catch(function() {
                    resultsDiv.innerHTML = '<div class="list-group-item text-danger py-3 text-center"><i class="ri ri-error-warning-line ri-lg me-2"></i>Search failed. Please try again.</div>';
                });
        }
        
        if (searchBtn) searchBtn.addEventListener('click', performSearch);
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') performSearch();
            });
        }
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchResults') && !e.target.closest('.input-group')) {
                resultsDiv.style.display = 'none';
            }
        });
        
        // Reset modal on close
        var addProductModal = document.getElementById('addProductModal');
        if (addProductModal) {
            addProductModal.addEventListener('hidden.bs.modal', function () {
                searchInput.value = '';
                selectedIdInput.value = '';
                selectedProductDisplay.classList.add('d-none');
                selectedProductName.textContent = '';
                addProductBtn.disabled = true;
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
            });
        }

        // Delete Modal Handler
        var deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var productId = button.getAttribute('data-product-id');
                var productName = button.getAttribute('data-product-name');
                
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteProductName').textContent = productName;
            });
        }
    })();
