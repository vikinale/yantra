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
                    [item text]Classes[/item]
                    [/array]
                    [/array]
                    [ll.pc.breadcrumb "{breadcrumb_list}" title="Classes" /]
                </div>
            </div>

            <div class="col-md-5 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex">
                            <div><h3 class="card-title"> <?= (isset($record['ID'])?"Update ":'Create New'); ?> Classes</h3></div>
                            <div class="text-right flex-grow-1">
                                <?php if(isset($record['ID'])): ?>
                                    <a href="[get env.site_url/]/admin/school/classes" class="btn btn-outline-info">Add New</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        [form.createform post action="{site_url}/admin/school/classes/create"]
                            <div class="error-container mb-3"></div>
                            <div class="message-container mb-3"></div>
                            <?php if(isset($record['ID'])): ?>
                                [ll.form-controls.hidden name="row_id" value="<?= $record['ID']; ?>" /]
                            <?php endif; ?>
                            [ll.form-controls.input  id="class_name" name="class_name" label="Class Name" value="<?= $record['class_name']??''; ?>" /]
                            [ll.form-controls.input  id="short_code" name="short_code" label="Short Code" value="<?= $record['code']??''; ?>" /]
                            <div class="row">
                                <div class="col"></div>
                                <div class="col-md-8 text-center">[ll.form-controls.submit  id="record_submit" name="record_submit" value="Save Classes" /]</div>
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
                            <h2 class="card-title">Classes List</h2>
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
        app.url('admin/school/classes/table'),
        Array({name: 'name', label: 'Name', sortable: true, order:'ASC',render:function (value,rec) {
                    let url = app.url('admin/school/classes/'+rec.ID);
                    return `<a class="btn btn-sm btn-link" title="Click to edit" href="${url}"> ${value} </a>`;
                }},
            { name: 'code', label: 'Short Code', sortable: true })
        ,{perPage: 10, orderColumn: 'name' });

    const formHandler = new yantra.FormHandler('#createform', true,{
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