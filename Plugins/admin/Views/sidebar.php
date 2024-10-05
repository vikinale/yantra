<div class="flapt-sidemenu-wrapper">
    <!-- Desktop Logo -->
    <div class="flapt-logo">
        <a href="index.html"
        ><img
                class="desktop-logo"
                src="<?= content_rul('media/') ?>logo.webp"
                alt="Desktop Logo" />
            <img
                class="small-logo"
                src="<?= content_rul('media/') ?>logo.webp"
                alt="Mobile Logo"
            /></a>
    </div>

    <!-- Side Nav -->
    <div class="flapt-sidenav" id="flaptSideNav">
        <!-- Side Menu Area -->
        <div class="side-menu-area">
            <!-- Sidebar Menu -->
            <nav>
                <ul class="sidebar-menu" data-widget="tree">
                    <li>
                        <a href=""><i class="bx bx-home-heart"></i><span>Dashboard</span></a>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-institution"></i><span>School</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/school'); ?>"><i class="fa fa-paperclip"></i><span>School Details</span></a> </li>
                            <li> <a href="<?= site_url('admin/school/academic-years'); ?>"><i class="fa fa-paperclip"></i><span>Academic Year</span></a> </li>
                            <li> <a href="<?= site_url('admin/school/educational-boards'); ?>"><i class="fa fa-paperclip"></i><span>Educational Board</span></a> </li>
                            <li> <a href="<?= site_url('admin/school/classes'); ?>"><i class="fa fa-paperclip"></i><span>Class</span></a> </li>
                            <li> <a href="<?= site_url('admin/school/divisions'); ?>"><i class="fa fa-paperclip"></i><span>Division</span></a> </li>
                            <li> <a href="<?= site_url('admin/school/subjects'); ?>"><i class="fa fa-paperclip"></i><span>Subject</span></a> </li>
                            <li> <a href="<?= site_url('admin/school/units'); ?>"><i class="fa fa-paperclip"></i><span>Unit</span></a> </li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-institution"></i><span>Branch</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/branches'); ?>"><i class="fa fa-paperclip"></i><span>Branches</span></a> </li>
                            <li> <a href="<?= site_url('admin/branches/create'); ?>"><i class="fa fa-paperclip"></i><span>Create</span></a> </li>
                            <li> <a href="<?= site_url('admin/classrooms'); ?>"><i class="fa fa-paperclip"></i><span>Classrooms</span></a> </li>
                            <li> <a href="<?= site_url('admin/student-group'); ?>"><i class="fa fa-paperclip"></i><span>Student Groups</span></a> </li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-book"></i><span>Textbooks</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/textbooks'); ?>"><i class="fa fa-paperclip"></i><span>Textbooks</span></a> </li>
                            <li> <a href="<?= site_url('admin/textbooks/chapters'); ?>"><i class="fa fa-paperclip"></i><span>Chapters</span></a> </li>
                            <li> <a href="<?= site_url('admin/textbooks/sections'); ?>"><i class="fa fa-paperclip"></i><span>Sections</span></a> </li>
                            <li> <a href="<?= site_url('admin/textbooks/subsections'); ?>"><i class="fa fa-paperclip"></i><span>Subsections</span></a> </li>
                            <li> <a href="<?= site_url('admin/textbooks/uploads'); ?>"><i class="fa fa-paperclip"></i><span>Upload PPT/ PDF</span></a> </li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-group"></i><span>Students</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/students'); ?>"><i class="fa fa-paperclip"></i><span>Students</span></a> </li>
                            <li> <a href="<?= site_url('admin/students/groups'); ?>"><i class="fa fa-paperclip"></i><span>Assign Groups</span></a> </li>
                            <li> <a href="<?= site_url('admin/students/logs'); ?>"><i class="fa fa-paperclip"></i><span>Logs</span></a> </li>
                        </ul>
                    </li>


                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-file-text"></i><span>Content</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/content'); ?>"><i class="fa fa-paperclip"></i><span>Content</span></a> </li>
                            <li> <a href="<?= site_url('admin/content/questions'); ?>"><i class="fa fa-paperclip"></i><span>Questions</span></a> </li>
                            <li> <a href="<?= site_url('admin/content/exporter'); ?>"><i class="fa fa-paperclip"></i><span>Exporter</span></a> </li>
                            <li> <a href="<?= site_url('admin/content/importer'); ?>"><i class="fa fa-paperclip"></i><span>Importer</span></a> </li>
                            <li> <a href="<?= site_url('admin/content/review-questions'); ?>"><i class="fa fa-paperclip"></i><span>Review Questions</span></a> </li>
                            <li> <a href="<?= site_url('admin/content/flash-cards'); ?>"><i class="fa fa-paperclip"></i><span>Flash Cards</span></a> </li>
                        </ul>
                    </li>


                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-question"></i><span>Quizzes</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/quizzes'); ?>"><i class="fa fa-paperclip"></i><span>Quizzes</span></a> </li>
                            <li> <a href="<?= site_url('admin/quizzes/create'); ?>"><i class="fa fa-paperclip"></i><span>Create Quiz</span></a> </li>
                        </ul>
                    </li>
                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-question"></i><span>Attempts</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/attempts'); ?>"><i class="fa fa-paperclip"></i><span>Attempts</span></a> </li>
                            <li> <a href="<?= site_url('admin/attempts/push-marks'); ?>"><i class="fa fa-paperclip"></i><span>Push Marks</span></a> </li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-object-group"></i><span>Offline Exams</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/offline-exams'); ?>"><i class="fa fa-paperclip"></i><span>Exams</span></a> </li>
                            <li> <a href="<?= site_url('admin/offline-exams/descriptive'); ?>"><i class="fa fa-paperclip"></i><span>Descriptive</span></a></li>
                            <li> <a href="<?= site_url('admin/offline-exams/omr'); ?>"><i class="fa fa-paperclip"></i><span>OMR</span></a> </li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-cogs"></i><span>Users</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/users'); ?>"><i class="fa fa-paperclip"></i><span>Users</span></a> </li>
                            <li> <a href="<?= site_url('admin/users/create'); ?>"><i class="fa fa-paperclip"></i><span>Create user</span></a> </li>
                            <li> <a href="<?= site_url('admin/users/roles'); ?>"><i class="fa fa-paperclip"></i><span>User Roles</span></a> </li>
                            <li> <a href="<?= site_url('admin/users/logs'); ?>"><i class="fa fa-paperclip"></i><span>User Logs</span></a> </li>
                        </ul>
                    </li>

                    <li class="treeview">
                        <a href="javascript:void(0)">
                            <i class="fa fa-cogs"></i><span>Manage</span>
                            <i class="fa fa-angle-right"></i>
                        </a>
                        <ul class="treeview-menu">
                            <li> <a href="<?= site_url('admin/settings'); ?>"><i class="fa fa-paperclip"></i><span>Settings</span></a> </li>
                            <li> <a href="<?= site_url('admin/logs'); ?>"><i class="fa fa-paperclip"></i><span>Logs</span></a> </li>
                        </ul>
                    </li>

                </ul>
            </nav>
        </div>

        <!-- Upgrade Card -->
        <div class="upgrade-card card">
            <div class="card-body">
                <img class="mb-4" src="<?= content_rul('media/') ?>bg-img/1.png" alt="" /> 
            </div>
        </div>
    </div>
</div>