<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [array breadcrumb_list]
                    [array.new]
                    [item href][get site_url/]/admin[/item]
                    [item text]Dashboard[/item]
                    [/array]
                    [array.new]
                    [item active]active[/item]
                    [item text]School[/item]
                    [/array]
                    [/array]
                    [ll.pc.breadcrumb "{breadcrumb_list}" title="School" /]
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h4 class="card-title">School Details</h4>
                        </div>
                        [form.school post action="{site_url}/admin/school/save"]
                        <div class="error-container mb-3"></div>
                        <div class="message-container mb-3"></div>
                        [ll.form-controls.input     id="name"   name="name" label="School Name" value="" /]
                        [ll.form-controls.textarea  id="address" name="address" label="Address" value="" length="255" /]
                        <div class="row">
                            <div class="col-md-6">
                                [ll.form-controls.input type="email" id="email" name="email" label="Email" value="" /]
                            </div>
                            <div class="col-md-6">
                                [ll.form-controls.input id="contact" name="contact" label="Contact" value="" /]
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                [ll.form-controls.input  id="city" name="city" label="City" value="" /]
                            </div>
                            <div class="col-md-6">
                                [ll.form-controls.input id="location" name="location" label="Location" value="" /]
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                [ll.form-controls.input id="pin" name="pin" label="Pin" value="" /]
                            </div>
                            <div class="col-md-6">
                            </div>
                        </div>
                        [ll.form-controls.submit id="school_submit" name="school_submit" value="Update School Details" /]
                        [/form]
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
