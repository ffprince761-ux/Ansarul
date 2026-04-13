    </div> <!-- End main-content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }

        // Auto-refresh dashboard every 5 minutes
        if (window.location.pathname.includes('dashboard.php')) {
            setTimeout(function() {
                location.reload();
            }, 300000); // 5 minutes
        }
    </script>
</body>
</html>
