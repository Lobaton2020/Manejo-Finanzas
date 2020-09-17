<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Finanzas</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-right">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Finanzas</a></li>
                            <li class="breadcrumb-item active">Listado</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card m-b-30">
                        <div class="m-3">
                            <div class="float-left">
                                <h4 class="mt-0  mb-3 header-title ">Listado de entradas de dinero</h4>
                            </div>
                            <div class="float-right">
                                <a href="<?php echo route("inflow/create") ?>" class="btn btn-success float-right"><i class="mdi mdi-plus "></i> Añadir Ingresos</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php echo renderMessage("success") ?>
                            <?php echo renderJumbotron($inflows, "No se ha encontrado ingresos", "inflow/create") ?>
                            <div>
                                <?php foreach ($inflows as $inflow) : ?>
                                    <div id="accordion-<?php echo $inflow->id_inflow ?>">
                                        <div class="card mb-0">
                                            <div class="card-header" data-toggle="collapse" data-parent="#accordion-<?php echo $inflow->id_inflow ?>" href="#collapse-<?php echo $inflow->id_inflow ?>" aria-expanded="false" aria-controls="collapseThree" id="headingThree">
                                                <h5 class="mb-0 mt-0 font-14">
                                                    <a class="collapsed text-dark">
                                                        <div class="my-0 float-left">
                                                            <h5 class="d-inline"><?php echo $inflow->inflow_type->name ?></h5>
                                                            <p class="d-inline align-center  w-100 p-2 border border-round"> <?php echo number_price($inflow->total) ?> - <?php echo format_date($inflow->set_date, false) ?> </p>
                                                            <b>
                                                                <span class="float-right text-green align-center"><i class="mdi mdi-menu-down-outline mdi-24px text-primary    "></i></span>
                                                            </b>
                                                        </div>
                                                        <div class="float-right">
                                                            <div class="dropdown mo-mb-2 show">
                                                                <small class="text-muted"><?php echo time_ago($inflow->create_at) ?></small>
                                                                <!-- <a class="btn btn-default btn-sm" href="#" id="detail-<?php echo $inflow->id_inflow ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                                    <i style="font-size:19px" class="mdi mdi-dots-horizontal"></i>
                                                                </a>
                                                                <div class="dropdown-menu " aria-labelledby="detail-<?php echo $inflow->id_inflow ?>" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 33px, 0px);">
                                                                    <a class="dropdown-item" href="<?php echo route("salary/edit/{$inflow->id_inflow}") ?>">Editar</a>
                                                                    <a class="dropdown-item" href="<?php echo route("salary/edit/{$inflow->id_inflow}") ?>">Eliminar</a>
                                                                </div> -->
                                                            </div>
                                                        </div>
                                                    </a>
                                                </h5>
                                            </div>
                                            <div id="collapse-<?php echo $inflow->id_inflow ?>" class="collapse" aria-labelledby="headingThree" data-parent="#accordion-<?php echo $inflow->id_inflow ?>">
                                                <div class="card-body">
                                                    <h4 class="mt-0 header-title mb-4">Destinacion del dinero</h4>
                                                    <ol class="activity-feed mb-0">
                                                        <?php foreach ($inflow->detail as $detail) : ?>
                                                            <li class="feed-item pb-1">
                                                                <p class="mb-1"><?php echo $detail->porcents->name ?></p>
                                                                <p class="font-15 mt-0 mb-2"><?php echo number_price($detail->value) ?> <b class="font-weight-bold"><?php echo $detail->porcent ?>%</b></p>
                                                            </li>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($inflow->detail)) : ?>
                                                            <p class="text-muted">Parece que no te propusiste metas de organizacion</p>
                                                        <?php endif; ?>
                                                    </ol>
                                                    <?php if (!empty($inflow->description)) : ?>
                                                        <hr>
                                                        <p class=""><?php echo $inflow->description ?></p>
                                                    <?php endif; ?>

                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div> <!-- end col -->


                    </div> <!-- end row -->
                </div>
            </div>


        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>