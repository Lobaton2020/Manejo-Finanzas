<?php if (!isset($page)) $page = ''; ?>

<!-- Main Content Wrapper -->
<div class="ml-64 pt-16 min-h-screen">
    <div class="p-6">
        <!-- Page Content -->
        <?php echo $content; ?>
    </div>
    
    <!-- Footer -->
    <footer class="border-t border-dark-700 py-6 px-6 text-center text-dark-400 text-sm">
        <p>&copy; <?php echo date('Y'); ?> Mis Finanzas. Todos los derechos reservados.</p>
    </footer>
</div>

<!-- jQuery -->
<script src="<?php echo URL_ASSETS ?>assets/js/jquery.min.js"></script>
<script src="<?php echo URL_ASSETS ?>assets/js/jquery.slim.min.js"></script>

<!-- Bootstrap JS -->
<script src="<?php echo URL_ASSETS ?>assets/js/bootstrap.bundle.min.js"></script>

<!-- MetisMenu -->
<script src="<?php echo URL_ASSETS ?>assets/template/js/metismenu.min.js"></script>

<!-- DataTables -->
<script src="<?php echo URL_ASSETS ?>assets/template/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo URL_ASSETS ?>assets/template/plugins/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Morris Charts -->
<script src="<?php echo URL_ASSETS ?>assets/template/plugins/morris/morris.min.js"></script>
<script src="<?php echo URL_ASSETS ?>assets/template/plugins/raphael/raphael-min.js"></script>

<!-- Custom App JS -->
<script src="<?php echo URL_ASSETS ?>assets/js/app.js"></script>

<!-- Page specific JS -->
<?php if (isset($page_js)): ?>
<script src="<?php echo URL_ASSETS ?>assets/js/<?php echo $page_js; ?>.js"></script>
<?php endif; ?>

<!-- Theme Toggle Script -->
<script>
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
    });
    
    // Sidebar toggle
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('aside');
    toggleSidebar.addEventListener('click', () => {
        sidebar.classList.toggle('-ml-64');
    });
</script>

</body>
</html>
