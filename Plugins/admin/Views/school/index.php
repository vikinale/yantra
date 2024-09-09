<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body card-breadcrumb">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">School details</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">School</a>
                                    </li>
                                    <li class="breadcrumb-item active">School details</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h4 class="card-title">Basic Details</h4>
                        </div>
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="school-name">School Name</label>
                                <input type="text" class="form-control" id="school-name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="school-address">Address</label>
                                <input type="text" class="form-control" id="school-address">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="school-city">City</label>
                                <input type="text" class="form-control" id="school-city">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="school-location">Location</label>
                                        <input type="text" class="form-control" id="school-location">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="school-pin">Pin-code</label>
                                        <input type="text" class="form-control" id="school-pin">
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="school-contact">Contact</label>
                                        <input type="text" class="form-control" id="school-contact">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="school-email">Email</label>
                                        <input type="email" class="form-control" id="school-email">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary w-md">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">
                            <h4 class="card-title">School Logo</h4>
                        </div>
                        <form>
                            <div class="d-flex align-items-center border-bottom pb-4 mb-4">
                                <div class="small-logo w-50">
                                    <img src="<?= content_rul('media/logo.webp') ?>" alt="Logo">
                                </div>
                                <div>
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">Choose Site Logo</label>
                                        <input class="form-control" type="file" id="site-logo" />
                                        <span class="fs-sm text-muted">( Upload a PNG or JPG, size limit is 200 KB. )</span>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary w-md">Save Logo</button>
                                        <button type="button" class="btn btn-danger w-md">Remove</button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
