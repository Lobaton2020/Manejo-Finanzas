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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Cook Tracking</a></li>
                            <li class="breadcrumb-item active">Calendario</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card m-b-30">
                        <div class="m-3">
                            <div class="float-left">
                                <h4 class="mt-0  mb-3 header-title">Calendario tracking de cocina delegada</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php echo renderMessage("success") ?>
                            <?php echo renderMessage("info") ?>
                            <?php echo renderMessage("error") ?>

                            <div class="row">
                                <div class="col-12">
                                    <div class="card m-b-30">
                                        <div class="card-body">

                                            <div class="row">
                                                <div class="col-xl-2 col-lg-3 col-md-4">

                                                    <h4 class="m-t-5 m-b-15 font-14">Created Events</h4>
                                                    <form method="post" id="add_event_form" class="m-t-5 m-b-20">
                                                        <input type="text" class="form-control new-event-form"
                                                            placeholder="Add new event..." />
                                                    </form>

                                                    <div id='external-events'>
                                                        <h4 class="m-b-15 font-14">Lista de eventos</h4>
                                                        <div class='fc-event'>Grays cocina</div>
                                                    </div>

                                                    <div id=''>
                                                        <h4 class="m-b-15 font-14">Facturacion</h4>
                                                        <label>Valor unitario</label>
                                                        <input disabled value="12.000" class="form-control"
                                                            id="valor-unitario">
                                                        <label>Total a pagar:</label>
                                                        <input disabled value="0" class="form-control" id="valor-total">

                                                        <label>Total en todo el tiempo:</label>
                                                        <input disabled value="0" class="form-control" id="total-todo">
                                                    </div>

                                                </div>

                                                <div id='calendar' class="col-xl-10 col-lg-9 col-md-8"></div>

                                            </div>
                                            <!-- end row -->

                                        </div>
                                    </div>
                                </div> <!-- end col -->
                            </div> <!-- end row -->
                        </div>
                    </div>
                </div> <!-- end col -->


            </div> <!-- end row -->
        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>