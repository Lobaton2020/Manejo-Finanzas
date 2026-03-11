<?php use app\core\Authentication; ?>
<!-- Navbar -->
<header class="fixed top-0 right-0 left-64 h-16 bg-dark-800/80 backdrop-blur-md border-b border-dark-700 z-40 flex items-center justify-between px-6">
    <!-- Left: Toggle & Search -->
    <div class="flex items-center gap-4">
        <button id="toggleSidebar" class="p-2 rounded-lg hover:bg-dark-700 text-dark-300">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Search -->
        <div class="relative">
            <input type="text" placeholder="Buscar..." class="w-64 bg-dark-700 border border-dark-600 rounded-lg px-4 py-2 pl-10 text-dark-200 placeholder-dark-400 focus:outline-none focus:border-primary-500">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
        </div>
    </div>
    
    <!-- Right: Actions -->
    <div class="flex items-center gap-4">
        <!-- Notifications -->
        <button class="relative p-2 rounded-lg hover:bg-dark-700 text-dark-300">
            <i class="fas fa-bell"></i>
            <?php if (isset($notifications_count) && $notifications_count > 0): ?>
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            <?php endif; ?>
        </button>
        
        <!-- Quick Add -->
        <div class="relative group">
            <button class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white rounded-lg">
                <i class="fas fa-plus"></i>
                <span>Nuevo</span>
            </button>
            <div class="absolute right-0 top-full mt-2 w-48 bg-dark-700 rounded-lg shadow-xl border border-dark-600 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                <a href="<?php echo URL_BASE ?>inflows/add" class="flex items-center gap-3 px-4 py-3 text-dark-200 hover:bg-dark-600 rounded-t-lg">
                    <i class="fas fa-arrow-up text-green-400"></i>
                    <span>Ingreso</span>
                </a>
                <a href="<?php echo URL_BASE ?>outflows/add" class="flex items-center gap-3 px-4 py-3 text-dark-200 hover:bg-dark-600 rounded-b-lg">
                    <i class="fas fa-arrow-down text-red-400"></i>
                    <span>Egreso</span>
                </a>
            </div>
        </div>
        
        <!-- Theme Toggle -->
        <button id="themeToggle" class="p-2 rounded-lg hover:bg-dark-700 text-dark-300">
            <i class="fas fa-moon"></i>
        </button>
        
        <!-- User Menu -->
        <div class="relative group">
            <button class="flex items-center gap-3 p-2 rounded-lg hover:bg-dark-700">
                <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-bold">
                    <?php echo strtoupper(substr(Authentication::user()->username ?? 'U', 0, 1)); ?>
                </div>
                <span class="text-dark-200"><?php echo Authentication::user()->username ?? 'Usuario' ?></span>
                <i class="fas fa-chevron-down text-dark-400 text-xs"></i>
            </button>
            <div class="absolute right-0 top-full mt-2 w-56 bg-dark-700 rounded-lg shadow-xl border border-dark-600 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                <a href="<?php echo URL_BASE ?>profile" class="flex items-center gap-3 px-4 py-3 text-dark-200 hover:bg-dark-600 rounded-t-lg">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
                <a href="<?php echo URL_BASE ?>settings" class="flex items-center gap-3 px-4 py-3 text-dark-200 hover:bg-dark-600">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
                <div class="border-t border-dark-600"></div>
                <a href="<?php echo URL_BASE ?>auth/logout" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-dark-600 rounded-b-lg">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </div>
    </div>
</header>
