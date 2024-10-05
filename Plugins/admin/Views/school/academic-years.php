<div class="content-wraper-area">
    <!-- Basic Form area Start -->
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    [ll.pc.breadcrumb "{breadcrumb}" title="Academic Year" /]
                </div>
            </div>

            <div class="col-md-5 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex">
                            <div><h3 class="card-title"> <?= (isset($record['ID'])?"Update ":'Create New'); ?> Academic Year</h3></div>
                            <div class="text-right flex-grow-1">
                                <?php if(isset($record['ID'])): ?>
                                    <a href="[get env.site_url/]/admin/school/academic-years" class="btn btn-outline-info">Add New</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        [form.academicyear post action="{site_url}/admin/school/academic-years/create"]
                            <div class="error-container mb-3"></div>
                            <div class="message-container mb-3"></div>

                            <?php if(isset($record['ID'])): ?>
                                [ll.form-controls.hidden name="year_id" value="<?= $record['ID']; ?>" /]
                            <?php endif; ?>

                            [ll.form-controls.input  id="year_name" name="year_name" label="Year Name" value="<?= $record['year_name']??''; ?>" /]

                            [ll.form-controls.daterange_picker label="Date Range" id="ac_year_date"
                            start_id="start_date" end_id="end_date"
                            name_start="start_date" name_end="end_date"
                            start_value="<?= $record['start_date']??''; ?>" end_value="<?= $record['end_date']??''; ?>" /]

                            <div class="row">
                                <div class="col"></div>
                                <div class="col-md-8 text-center">[ll.form-controls.submit  id="permission_submit" name="permission_submit" value="Save Academic Year" /]</div>
                                <div class="col"></div>
                            </div>
                        [/form]

                    </div>
                </div>
            </div>

            <div class="col-md-7 col-xl-8">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="card-title">
                            <h2 class="card-title">Academic Year List</h2>
                        </div>
                        <div class="w-100 overflow-auto">
                            <div id="table-filters"></div>
                            <div id="data-table">
                                <div class="table-container bg-white">
                                    <table class="table table-bordered table-striped table-light"></table>
                                </div>
                                <div class="pagination-container"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

[actions.footer]
<script type="text/javascript">
    const table = new yantra.JsonTable('#data-table',
        app.url('admin/school/academic-years/table'),
        Array(
            {name: 'name', label: 'Name', sortable: true, order:'DESC',render:function (value,rec) {
                    let url = app.url('admin/school/academic-years/'+rec.ID);
                    return `<a class="btn btn-sm btn-link" title="Click to edit" href="${url}"> ${value} </a>`;
                }},
            { name: 'start', label: 'Start Date', sortable: true },
            { name: 'end', label: 'End Date', sortable: true } ),
        {perPage: 5, orderColumn: 'name'}
    );

    const formHandler = new yantra.FormHandler('#academicyear', true,{
        onSuccess:function (response){
            table.refresh();
            return true;
        },
        onFormError:function (response){
            return true;
        },
        onError:function (error,frm){
            console.log(error);
        }
    });
</script>
[/actions]