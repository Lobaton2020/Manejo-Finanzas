<div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Menú</li>
                <li>
                    <a href="<?php echo route("main") ?>" class="waves-effect">
                        <i class="icon-accelerator"></i><span class="badge badge-danger badge-pill float-right">&nbsp;</span> <span> Pagina principal </span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo route("inflow") ?>" class=""><i class=" size-icon mdi mdi-trending-up "></i><span>Ingresos<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("outflow") ?>" class=""><i class=" size-icon mdi mdi-trending-down"></i><span>Egresos<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("porcent") ?>" class=""><i class="size-icon mdi mdi-view-compact"></i><span>Depositos<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("moveType") ?>" class=""><i class="size-icon mdi mdi-bank-plus"></i><span>Tipo movimientos<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("category") ?>" class=""><i class="size-icon mdi mdi-border-all"></i><span>Categorias de egresos<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("note") ?>" class=""><i class="size-icon mdi mdi-book"></i><span>Notas<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("moneyLoan") ?>" class=""><i class="size-icon mdi mdi-block-helper"></i><span>Prestamos<span class="float-right menu-arrow"> </span></a>
                </li>
                <li>
                    <a href="<?php echo route("budget") ?>" class=""><i class="size-icon fas fa-plane"></i><span>Presupuesto<span
                                class="float-right menu-arrow">
                            </span></a>
                </li>
                <?php if ($self["rol"]["name"] == "Administrador") : ?>
                    <li>
                        <a href="<?php echo route("query") ?>" class=""><i class="size-icon mdi mdi mdi-database-edit"></i><span>SQL<span class="float-right menu-arrow"> </span></a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="waves-effect"><i class="size-icon mdi mdi-flash-auto"></i><span> Administracion <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span> </span></a>
                        <ul class="submenu">
                            <li><a href="<?php echo route("admin/users") ?>">Usuarios</a></li>
                            <li><a href="<?php echo route("admin/notifications") ?>">Notificaciones</a></li>
                            <li><a href="<?php echo route("admin/visits") ?>">Visitas</a></li>
                            <li><a href="<?php echo route("admin/tokens") ?>">Tokens de registro</a></li>
                            <li><a href="<?php echo route("admin/loggins") ?>">Historico de ingreso</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
        <!-- Sidebar -->
        <div class="clearfix"></div>

    </div>
    <!-- Sidebar -left -->

</div>