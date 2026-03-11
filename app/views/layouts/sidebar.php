<?php use app\core\Authentication; ?>
<!-- Sidebar -->
<aside class="fixed left-0 top-0 h-screen w-64 bg-dark-800 border-r border-dark-700 flex flex-col z-50">
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b border-dark-700">
        <a href="<?php echo URL_BASE ?>" class="flex items-center gap-3">
            <img src="<?php echo URL_ASSETS ?>assets/img/logo.png" alt="Logo" class="h-10 w-10">
            <span class="text-xl font-bold text-white">Mis Finanzas</span>
        </a>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
            <!-- Dashboard -->
            <li>
                <a href="<?php echo URL_BASE ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'dashboard' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Ingresos -->
            <li>
                <a href="<?php echo URL_BASE ?>inflows" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'inflows' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-arrow-up text-green-400 w-5"></i>
                    <span>Ingresos</span>
                </a>
            </li>
            
            <!-- Egresos -->
            <li>
                <a href="<?php echo URL_BASE ?>outflows" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'outflows' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-arrow-down text-red-400 w-5"></i>
                    <span>Egresos</span>
                </a>
            </li>
            
            <!-- Presupuestos -->
            <li>
                <a href="<?php echo URL_BASE ?>budgets" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'budgets' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-wallet w-5"></i>
                    <span>Presupuestos</span>
                </a>
            </li>
            
            <!-- Inversiones -->
            <li>
                <a href="<?php echo URL_BASE ?>investments" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'investments' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-chart-line text-purple-400 w-5"></i>
                    <span>Inversiones</span>
                </a>
            </li>
            
            <!-- Préstamos -->
            <li>
                <a href="<?php echo URL_BASE ?>loans" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'loans' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-hand-holding-usd text-yellow-400 w-5"></i>
                    <span>Préstamos</span>
                </a>
            </li>
            
            <!-- Estadísticas -->
            <li>
                <a href="<?php echo URL_BASE ?>statistics" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'statistics' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-chart-pie text-blue-400 w-5"></i>
                    <span>Estadísticas</span>
                </a>
            </li>
            
            <!-- Configuración -->
            <li class="pt-4 mt-4 border-t border-dark-700">
                <span class="px-4 text-xs text-dark-500 uppercase tracking-wider">Configuración</span>
            </li>
            
            <!-- Categorías -->
            <li>
                <a href="<?php echo URL_BASE ?>categories" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'categories' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-tags w-5"></i>
                    <span>Categorías</span>
                </a>
            </li>
            
            <!-- Porcentajes -->
            <li>
                <a href="<?php echo URL_BASE ?>porcents" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'porcents' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-percentage w-5"></i>
                    <span>Porcentajes</span>
                </a>
            </li>
            
            <!-- Notas -->
            <li>
                <a href="<?php echo URL_BASE ?>notes" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'notes' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-sticky-note w-5"></i>
                    <span>Notas</span>
                </a>
            </li>
            
            <!-- Admin -->
            <?php if (Authentication::isAdmin()): ?>
            <li class="pt-4 mt-4 border-t border-dark-700">
                <span class="px-4 text-xs text-dark-500 uppercase tracking-wider">Admin</span>
            </li>
            <li>
                <a href="<?php echo URL_BASE ?>admin" class="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-300 hover:bg-dark-700 hover:text-white <?php echo $page == 'admin' ? 'bg-primary-600 text-white' : '' ?>">
                    <i class="fas fa-cog w-5"></i>
                    <span>Panel Admin</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <!-- User -->
    <div class="border-t border-dark-700 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold">
                <?php echo strtoupper(substr(Authentication::user()->username ?? 'U', 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?php echo Authentication::user()->username ?? 'Usuario' ?></p>
                <p class="text-xs text-dark-400 truncate"><?php echo Authentication::user()->email ?? '' ?></p>
            </div>
            <a href="<?php echo URL_BASE ?>auth/logout" class="p-2 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-white" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>
