layui.use(['element', 'layer', 'jquery', 'table', 'form'], function(){
    var element = layui.element;
    var layer = layui.layer;
    var $ = layui.jquery;
    var table = layui.table;
    var form = layui.form;
    
    // 导航点击事件
    element.on('nav(admin-nav)', function(elem) {
        // 获取lay-href属性
        var href = elem.attr('lay-href');
        if(href && href.startsWith('#')) {
            // 隐藏所有内容卡片
            $('.content-main .card').hide();
            // 显示对应的内容卡片
            $(href).show();
            
            // 如果是分类管理，初始化表格
            if(href === '#categories') {
                initCategoriesTable();
            }
            // 如果是题目管理，初始化表格
            else if(href === '#questions') {
                initQuestionsTable();
            }
            // 如果是题库管理，初始化表格
            else if(href === '#banks') {
                initBanksTable();
            }
            // 如果是错题/收藏管理，初始化表格
            else if(href === '#wrong-questions') {
                initWrongQuestionsTable();
            }
        }
    });
    
    // 自定义表单验证规则
    form.verify({
        // 题目类型必填
        requiredType: function(value, item) {
            if(!value) {
                return '题目类型不能为空';
            }
        },
        // 题目内容必填
        requiredContent: function(value, item) {
            if(!value || value.trim() === '') {
                return '题目内容不能为空';
            }
        },
        // 答案必填
        requiredAnswer: function(value, item) {
            if(!value || value.trim() === '') {
                return '答案不能为空';
            }
        },
        // 选项内容必填
        requiredOption: function(value, item) {
            if(!value || value.trim() === '') {
                return '选项内容不能为空';
            }
        }
    });
    
    // 模拟数据统计
    function loadStats() {
        // 这里可以通过AJAX从服务器获取真实数据
        // 暂时使用模拟数据
        $('#total-questions').text('1,234');
        $('#total-users').text('567');
        $('#total-wrong').text('89');
        $('#total-categories').text('12');
    }
    
    // 分类管理相关功能
    // 初始化分类表格
    function initCategoriesTable() {
        table.render({
            elem: '#categories-table',
            url: 'api/categories.php',
            method: 'GET',
            page: false,
            limit: 20,
            height: 'full-150',
            request: {
                pageName: 'page', // 页码的参数名称，默认：page
                limitName: 'limit' // 每页数据量的参数名，默认：limit
            },
            response: {
                statusName: 'code', // 规定数据状态的字段名称，默认：code
                statusCode: 200, // 规定成功的状态码，默认：0
                msgName: 'msg', // 规定状态信息的字段名称，默认：msg
                countName: 'count', // 规定数据总数的字段名称，默认：count
                dataName: 'data' // 规定数据列表的字段名称，默认：data
            },
            cols: [[
                {field: 'id', title: 'ID', width: 80, align: 'center'},
                {field: 'name', title: '分类名称', minWidth: 120},
                {field: 'description', title: '描述', minWidth: 150},
                {field: 'parent_id', title: '上级分类ID', width: 120, align: 'center'},
                {field: 'status', title: '状态', width: 100, align: 'center', templet: '<div>{{d.status === 1 ? "启用" : "禁用"}}</div>'},
                {field: 'created_at', title: '创建时间', width: 160, align: 'center'},
                {fixed: 'right', title: '操作', width: 160, align: 'center', toolbar: '#categories-toolbar'}
            ]],
            done: function(res) {
                console.log('表格加载完成', res);
                if(res.code === 200) {
                    // 更新统计数据
                    $('#total-categories').text(res.data.length);
                    // 加载分类选项到下拉框
                    loadCategoryOptions(res.data);
                }
            }
        });
        
        // 绑定工具栏事件
        table.on('tool(categories-filter)', function(obj) {
            var data = obj.data;
            if(obj.event === 'edit') {
                // 编辑分类
                editCategory(data);
            } else if(obj.event === 'del') {
                // 删除分类
                deleteCategory(data.id);
            }
        });
    }
    
    // 加载分类选项到下拉框
    function loadCategoryOptions(categories) {
        var select = $('select[name="parent_id"]');
        // 清空现有选项（保留顶级分类）
        select.find('option:not([value="0"])').remove();
        
        // 添加分类选项
        categories.forEach(function(category) {
            if(category.parent_id === 0) {
                select.append('<option value="' + category.id + '">' + category.name + '</option>');
            }
        });
        
        // 重新渲染表单
        form.render('select');
    }
    
    // 添加分类按钮点击事件
    $('#add-category-btn').on('click', function() {
        openCategoryModal();
    });
    
    // 打开分类模态框
    function openCategoryModal(category) {
        // 清空表单
        $('#category-form')[0].reset();
        $('input[name="id"]').val('');
        
        // 如果是编辑，填充表单数据
        if(category) {
            $('input[name="id"]').val(category.id);
            $('input[name="name"]').val(category.name);
            $('select[name="parent_id"]').val(category.parent_id);
            $('textarea[name="description"]').val(category.description);
            // 重新渲染表单
            form.render();
        }
        
        // 打开模态框
        layer.open({
            type: 1,
            title: category ? '编辑分类' : '添加分类',
            content: $('#category-modal'),
            area: ['500px', 'auto'],
            btn: ['确定', '取消'],
            btn1: function(index, layero) {
                // 提交表单
                form.on('submit(category-form)', function(data) {
                    saveCategory(data.field);
                    layer.close(index);
                    return false;
                });
                
                // 触发表单提交
                $('#category-form').find('button[type="submit"]').trigger('click');
            }
        });
    }
    
    // 编辑分类
    function editCategory(category) {
        openCategoryModal(category);
    }
    
    // 保存分类
    function saveCategory(categoryData) {
        var url = 'api/categories.php';
        var method = categoryData.id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(categoryData),
            success: function(res) {
                if(res.code === 200 || res.code === 201) {
                    layer.msg(res.msg, {icon: 1});
                    // 重新加载表格
                    table.reload('categories-table');
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function(xhr, status, error) {
                layer.msg('操作失败: ' + error, {icon: 2});
            }
        });
    }
    
    // 删除分类
    function deleteCategory(id) {
        layer.confirm('确定要删除这个分类吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            $.ajax({
                url: 'api/categories.php?id=' + id,
                method: 'DELETE',
                success: function(res) {
                    if(res.code === 200) {
                        layer.msg(res.msg, {icon: 1});
                        // 重新加载表格
                        table.reload('categories-table');
                    } else {
                        layer.msg(res.msg, {icon: 2});
                    }
                },
                error: function(xhr, status, error) {
                    layer.msg('删除失败: ' + error, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    }
    
    // 监听表单提交
    form.on('submit(category-form)', function(data) {
        return false; // 阻止默认提交
    });
    
    // 错题/收藏管理相关功能
    // 初始化错题/收藏表格
    var currentType = 0; // 当前显示类型：0-错题，1-收藏
    
    // 初始化错题统计
    function initWrongStats() {
        // 这里可以添加错题统计的初始化逻辑
        // 例如加载统计数据、初始化图表等
        console.log('初始化错题统计');
        
        // 暂时使用模拟数据更新统计卡片
        $('#total-wrong-count').text('89');
        $('#total-favorite-count').text('45');
        $('#total-unreviewed-count').text('32');
        $('#total-reviewed-count').text('57');
    }
    
    function initWrongQuestionsTable() {
        table.render({
            elem: '#wrong-questions-table',
            url: 'api/wrong-questions.php',
            method: 'GET',
            page: false,
            limit: 20,
            height: 'full-150',
            where: {
                type: currentType
            },
            request: {
                pageName: 'page', // 页码的参数名称，默认：page
                limitName: 'limit' // 每页数据量的参数名，默认：limit
            },
            response: {
                statusName: 'code', // 规定数据状态的字段名称，默认：code
                statusCode: 200, // 规定成功的状态码，默认：0
                msgName: 'msg', // 规定状态信息的字段名称，默认：msg
                countName: 'count', // 规定数据总数的字段名称，默认：count
                dataName: 'data' // 规定数据列表的字段名称，默认：data
            },
            cols: [[
                {field: 'id', title: 'ID', width: 80, align: 'center'},
                {field: 'user_id', title: '用户ID', width: 100, align: 'center'},
                {field: 'question.content', title: '题目内容', minWidth: 300},
                {field: 'bank_name', title: '所属题库', width: 150, align: 'center'},
                {field: 'category_name', title: '分类', width: 150, align: 'center'},
                {field: 'type', title: '类型', width: 100, align: 'center', templet: '<div>{{d.type === 0 ? "错题" : "收藏"}}</div>'},
                {field: 'reviewed', title: '复习状态', width: 120, align: 'center', templet: '<div>{{d.type === 0 ? (d.reviewed === 1 ? "已复习" : "未复习") : "-"}}</div>'},
                {field: 'created_at', title: '创建时间', width: 160, align: 'center'},
                {field: 'reviewed_at', title: '复习时间', width: 160, align: 'center', templet: '<div>{{d.reviewed_at || "-"}}</div>'},
                {fixed: 'right', title: '操作', width: 180, align: 'center', toolbar: '#wrong-questions-toolbar'}
            ]],
            done: function(res) {
                if(res.code === 200) {
                    // 更新统计数据
                    if(currentType === 0) {
                        var reviewedCount = res.data.filter(item => item.reviewed === 1).length;
                        var unreviewedCount = res.data.length - reviewedCount;
                        $('#total-wrong-count').text(res.data.length);
                        $('#total-reviewed-count').text(reviewedCount);
                        $('#total-unreviewed-count').text(unreviewedCount);
                    } else {
                        $('#total-favorite-count').text(res.data.length);
                    }
                }
            }
        });
        
        // 绑定工具栏事件
        table.on('tool(wrong-questions-filter)', function(obj) {
            var data = obj.data;
            if(obj.event === 'review') {
                // 标记为已复习
                reviewWrongQuestion(data.id);
            } else if(obj.event === 'del') {
                // 删除错题/收藏
                deleteWrongQuestion(data.id);
            } else if(obj.event === 'toggle-type') {
                // 切换类型
                toggleWrongQuestionType(data.id, data.type);
            }
        });
    }
    
    // 标记为已复习
    function reviewWrongQuestion(id) {
        $.ajax({
            url: 'api/wrong-questions.php',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({id: id, reviewed: 1}),
            dataType: 'json',
            success: function(res) {
                if(res.code === 200) {
                    layer.msg(res.msg, {icon: 1});
                    // 重新加载表格
                    table.reload('wrong-questions-table');
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function(xhr, status, error) {
                layer.msg('操作失败: ' + error, {icon: 2});
            }
        });
    }
    
    // 删除错题/收藏
    function deleteWrongQuestion(id) {
        layer.confirm('确定要删除这条记录吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            $.ajax({
                url: 'api/wrong-questions.php?id=' + id,
                method: 'DELETE',
                dataType: 'json',
                success: function(res) {
                    if(res.code === 200) {
                        layer.msg(res.msg, {icon: 1});
                        // 重新加载表格
                        table.reload('wrong-questions-table');
                    } else {
                        layer.msg(res.msg, {icon: 2});
                    }
                },
                error: function(xhr, status, error) {
                    layer.msg('删除失败: ' + error, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    }
    
    // 切换类型
    function toggleWrongQuestionType(id, currentType) {
        var newType = currentType === 0 ? 1 : 0;
        var actionText = newType === 0 ? '错题' : '收藏';
        
        layer.confirm('确定要切换为' + actionText + '吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            $.ajax({
                url: 'api/wrong-questions.php',
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify({id: id, type: newType}),
                dataType: 'json',
                success: function(res) {
                    if(res.code === 200) {
                        layer.msg(res.msg, {icon: 1});
                        // 重新加载表格
                        table.reload('wrong-questions-table');
                    } else {
                        layer.msg(res.msg, {icon: 2});
                    }
                },
                error: function(xhr, status, error) {
                    layer.msg('操作失败: ' + error, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    }
    
    // 类型切换按钮点击事件
    $('.layui-btn-group').on('click', 'button', function() {
        var type = parseInt($(this).data('type'));
        if(type === currentType) return;
        
        // 更新按钮样式
        $('.layui-btn-group button').removeClass('layui-btn-primary');
        $('.layui-btn-group button').addClass('layui-btn-primary');
        $(this).removeClass('layui-btn-primary');
        
        // 更新当前类型
        currentType = type;
        
        // 重新加载表格
        initWrongQuestionsTable();
    });
    
    // 搜索按钮点击事件
    $('#search-wrong-question').on('click', function() {
        var reviewed = $('#search-reviewed').val();
        
        // 重新加载表格
        table.reload('wrong-questions-table', {
            where: {
                type: currentType,
                reviewed: reviewed
            }
        });
    });
    
    // 重置按钮点击事件
    $('#reset-wrong-question').on('click', function() {
        $('#search-user').val('');
        $('#search-reviewed').val('-1');
        form.render('select');
        
        // 重新加载表格
        table.reload('wrong-questions-table', {
            where: {
                type: currentType,
                reviewed: -1
            }
        });
    });
    
    // 题目管理相关功能
    // 初始化题目表格
    function initQuestionsTable() {
        table.render({
            elem: '#questions-table',
            url: 'api/questions.php',
            method: 'GET',
            page: false,
            limit: 20,
            height: 'full-150',
            request: {
                pageName: 'page', // 页码的参数名称，默认：page
                limitName: 'limit' // 每页数据量的参数名，默认：limit
            },
            response: {
                statusName: 'code', // 规定数据状态的字段名称，默认：code
                statusCode: 200, // 规定成功的状态码，默认：0
                msgName: 'msg', // 规定状态信息的字段名称，默认：msg
                countName: 'count', // 规定数据总数的字段名称，默认：count
                dataName: 'data' // 规定数据列表的字段名称，默认：data
            },
            cols: [[
                {field: 'id', title: 'ID', width: 80, align: 'center'},
                {field: 'type', title: '类型', width: 100, align: 'center', templet: '<div>{{d.type === 1 ? "单选" : d.type === 2 ? "多选" : d.type === 3 ? "判断" : d.type === 4 ? "填空" : "简答"}}</div>'},
                {field: 'content', title: '题目内容', minWidth: 300},
                {field: 'difficulty', title: '难度', width: 100, align: 'center', templet: '<div>{{d.difficulty === 1 ? "简单" : d.difficulty === 2 ? "中等" : "困难"}}</div>'},
                {field: 'status', title: '状态', width: 100, align: 'center', templet: '<div>{{d.status === 1 ? "启用" : "禁用"}}</div>'},
                {field: 'created_at', title: '创建时间', width: 160, align: 'center'},
                {fixed: 'right', title: '操作', width: 160, align: 'center', toolbar: '#questions-toolbar'}
            ]],
            done: function(res) {
                if(res.code === 200) {
                    // 更新统计数据
                    $('#total-questions').text(res.data.length);
                }
            }
        });
        
        // 绑定工具栏事件
        table.on('tool(questions-filter)', function(obj) {
            var data = obj.data;
            if(obj.event === 'edit') {
                // 编辑题目
                editQuestion(data);
            } else if(obj.event === 'del') {
                // 删除题目
                deleteQuestion(data.id);
            }
        });
        
        // 加载分类选项
        loadQuestionCategories();
        // 加载题库选项
        loadBankOptions();
    }
    
    // 加载分类选项（只用于题库添加表单）
    function loadQuestionCategories() {
        $.ajax({
            url: 'api/categories.php',
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if(res.code === 200) {
                    var categories = res.data;
                    // 只用于题库添加表单的分类选择
                    var select = $('select[name="category_id"]');
                    // 清空现有选项（保留顶级分类）
                    select.find('option:not([value="0"])').remove();
                    
                    // 添加分类选项
                    categories.forEach(function(category) {
                        if(category.parent_id === 0) {
                            select.append('<option value="' + category.id + '">' + category.name + '</option>');
                        }
                    });
                    
                    // 重新渲染表单
                    form.render('select');
                }
            }
        });
    }
    
    // 题目类型切换事件
    form.on('select(question-type)', function(data) {
        var type = parseInt(data.value);
        if(type === 1 || type === 2 || type === 3) {
            // 选择题和判断题，显示选项输入
            $('.choice-options').show();
            $('.answer-section').hide();
            
            // 显示添加选项按钮，使用和单选一样的UI
            $('#add-option').show();
        } else {
            // 非选择题，显示答案输入
            $('.choice-options').hide();
            $('.answer-section').show();
        }
    });
    
    // 添加选项按钮点击事件
    $('#add-option').on('click', function() {
        var container = $('.options-container');
        var index = container.find('.option-item').length;
        var letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        var letter = letters[index] || String.fromCharCode(65 + index); // A的ASCII码是65
        
        var optionHtml = '<div class="option-item" style="margin-bottom: 10px;">' +
            '<div class="layui-row">' +
                '<div class="layui-col-md1">' +
                    '<div class="layui-form-mid layui-word-aux" style="font-weight: bold; font-size: 16px;">' + letter + '</div>' +
                    '<input type="hidden" name="options[' + index + '][letter]" value="' + letter + '">' +
                '</div>' +
                '<div class="layui-col-md10">' +
                    '<input type="text" name="options[' + index + '][content]" placeholder="选项内容" class="layui-input" style="margin-bottom: 5px;" lay-verify="requiredOption">' +
                '</div>' +
                '<div class="layui-col-md1">' +
                    '<input type="checkbox" name="options[' + index + '][is_correct]" lay-skin="primary" title="正确">' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.append(optionHtml);
        form.render('checkbox');
        // 重新渲染表单验证规则
        form.render();
        // 绑定新添加选项的事件
        bindOptionEvents();
        // 同步到批量输入
        syncSingleToBatch();
    });
    
    // 绑定选项事件
    function bindOptionEvents() {
        // 绑定单条选项内容输入事件
        $('.option-item input[name^="options["][name$="][content]"]').off('input').on('input', function() {
            syncSingleToBatch();
        });
        
        // 绑定选项正确答案选择事件（使用 LayUI 事件监听）
        form.on('checkbox', function(data) {
            if($(data.elem).closest('.option-item').length > 0) {
                syncCheckboxToAnswer();
                updateQuestionType();
            }
        });
    }
    
    // 单条输入到批量输入的同步
    function syncSingleToBatch() {
        var options = [];
        $('.option-item input[name^="options["][name$="][content]"]').each(function() {
            var content = $(this).val().trim();
            if(content) {
                options.push(content);
            }
        });
        $('input[name="batch_options"]').val(options.join('|'));
    }
    
    // 批量输入到单条输入的同步
    function syncBatchToSingle() {
        var batchContent = $('input[name="batch_options"]').val().trim();
        if(!batchContent) return;
        
        var options = batchContent.split('|');
        var container = $('.options-container');
        
        // 清空现有选项
        container.html('');
        
        // 生成新的选项
        options.forEach(function(option, index) {
            var optionContent = option.trim();
            if(optionContent) {
                // 根据索引生成字母（A的ASCII码是65）
                var letter = String.fromCharCode(65 + index);
                var optionHtml = '<div class="option-item" style="margin-bottom: 10px;">' +
                    '<div class="layui-row">' +
                        '<div class="layui-col-md1">' +
                            '<div class="layui-form-mid layui-word-aux" style="font-weight: bold; font-size: 16px;">' + letter + '</div>' +
                            '<input type="hidden" name="options[' + index + '][letter]" value="' + letter + '">' +
                        '</div>' +
                        '<div class="layui-col-md10">' +
                            '<input type="text" name="options[' + index + '][content]" value="' + optionContent + '" placeholder="选项内容" class="layui-input" style="margin-bottom: 5px;" lay-verify="requiredOption">' +
                        '</div>' +
                        '<div class="layui-col-md1">' +
                            '<input type="checkbox" name="options[' + index + '][is_correct]" lay-skin="primary" title="正确">' +
                        '</div>' +
                    '</div>' +
                '</div>';
                container.append(optionHtml);
            }
        });
        
        // 重新渲染表单
        form.render();
        // 绑定事件
        bindOptionEvents();
        // 同步答案到复选框
        syncAnswerToCheckbox();
        // 更新题型
        updateQuestionType();
    }
    
    // 单条选择框到答案输入框的同步
    function syncCheckboxToAnswer() {
        var answer = '';
        // 遍历所有选项，检查是否被选中
        $('.option-item').each(function(index) {
            var optionItem = $(this);
            var isChecked = false;
            
            // 检查是否有选中的复选框或单选按钮
            var checkbox = optionItem.find('input[type="checkbox"]');
            var radio = optionItem.find('input[type="radio"]');
            
            if(checkbox.length > 0) {
                isChecked = checkbox.prop('checked');
            } else if(radio.length > 0) {
                // 判断题的单选按钮特殊处理
                var currentType = $('select[name="type"]').val();
                if(currentType === '3') {
                    // 判断题，检查当前单选按钮是否被选中
                    isChecked = radio.prop('checked');
                } else {
                    isChecked = radio.prop('checked');
                }
            }
            
            if (isChecked) {
                // 直接从选项元素中获取字母
                var letter = optionItem.find('.layui-form-mid').text().trim();
                answer += letter;
            }
        });
        $('input[name="answer"]').val(answer);
    }
    
    // 答案输入框到单条选择框的同步
    function syncAnswerToCheckbox() {
        var answer = $('input[name="answer"]').val().toUpperCase();
        var currentType = $('select[name="type"]').val();
        
        // 清空所有选择框和单选按钮
        $('.option-item input[name^="options["][name$="][is_correct]"]').prop('checked', false);
        $('.option-item input[name="judgment_option_is_correct"]').prop('checked', false);
        
        // 选中对应选择框或单选按钮
        $('.option-item').each(function() {
            // 直接从选项元素中获取字母
            var itemLetter = $(this).find('.layui-form-mid').text().trim().toUpperCase();
            if(answer.includes(itemLetter)) {
                // 查找当前选项的正确答案选择器
                var correctSelector = $(this).find('input[name^="options["][name$="][is_correct]"]');
                var judgmentSelector = $(this).find('input[name="judgment_option_is_correct"]');
                
                if(correctSelector.length > 0) {
                    correctSelector.prop('checked', true);
                } else if(judgmentSelector.length > 0) {
                    judgmentSelector.prop('checked', true);
                }
            }
        });
        
        // 重新渲染表单
        form.render();
    }
    
    // 更新题型
    function updateQuestionType() {
        var currentType = $('select[name="type"]').val();
        
        // 只处理单选和多选题型的自动切换
        if(currentType === '1' || currentType === '2') {
            var answer = $('input[name="answer"]').val().toUpperCase();
            var checkedCount = $('.option-item input[name^="options["][name$="][is_correct]"]:checked').length;
            var type = '1'; // 默认单选
            
            // 当答案长度>1或选中数量>1时，自动切换为多选
            if(answer.length > 1 || checkedCount > 1) {
                type = '2'; // 多选
            }
            
            $('select[name="type"]').val(type);
            // 重新渲染表单
            form.render();
        }
    }
    
    // 初始化事件绑定
    function initOptionEvents() {
        // 加载题库选项
        loadBankOptions();
        
        // 绑定批量输入事件
        $('input[name="batch_options"]').off('input').on('input', function() {
            syncBatchToSingle();
        });
        
        // 绑定答案输入事件
        $('input[name="answer"]').off('input').on('input', function() {
            syncAnswerToCheckbox();
            updateQuestionType();
        });
        
        // 绑定题型变化事件
        form.on('select(question-type)', function(data) {
            var type = data.value;
            if(type === '1' || type === '2' || type === '3') {
                // 选择题和判断题，显示选项输入
                $('.choice-options').show();
                $('.answer-section').hide();
            } else {
                // 非选择题，显示答案输入
                $('.choice-options').hide();
                $('.answer-section').show();
            }
        });
        
        // 初始绑定单条选项事件
        bindOptionEvents();
        // 初始同步单条输入到批量输入
        syncSingleToBatch();
        
        // 初始触发类型变化事件，显示正确的输入区域
        $('select[name="type"]').trigger('change');
        
        // 重新渲染表单
        form.render();
    }
    
    // 监听题目内容输入事件，检测tab分隔符
    $('textarea[name="content"]').on('input', function() {
        var content = $(this).val();
        if(content.includes('\t')) {
            var parts = content.split('\t');
            if(parts.length >= 4) {
                // 第一项是题目内容
                $(this).val(parts[0]);
                
                // 第二项是选项内容，用竖线分隔
                var options = parts[1].trim();
                if(options) {
                    $('input[name="batch_options"]').val(options);
                    syncBatchToSingle();
                }
                
                // 第三项是答案
                var answer = parts[2].trim();
                if(answer) {
                    $('input[name="answer"]').val(answer);
                    syncAnswerToCheckbox();
                    updateQuestionType();
                }
                
                // 第四项是解析
                var analysis = parts[3].trim();
                if(analysis) {
                    $('textarea[name="analysis"]').val(analysis);
                }
            }
        }
    });
    
    // 初始化选项事件
    initOptionEvents();
    
    // 添加题目按钮点击事件
    $('#add-question-btn').on('click', function() {
        // 隐藏题目列表，显示添加题目表单
        $('#questions').hide();
        $('#add-question').show();
        
        // 清空表单
        $('#question-form')[0].reset();
        $('input[name="id"]').val('');
        
        // 切换到单选类型
        $('select[name="type"]').val('1').trigger('change');
        
        // 加载题库选项
        loadBankOptions();
        
        form.render('select');
        
        // 重新绑定选项事件
        initOptionEvents();
    });
    
    // 返回列表按钮点击事件
    $('#back-to-questions').on('click', function() {
        // 隐藏添加题目表单，显示题目列表
        $('#add-question').hide();
        $('#questions').show();
    });
    
    // 搜索按钮点击事件
    $('#search-btn').on('click', function() {
        var bank_id = $('#search-bank').val();
        var type = $('#search-type').val();
        
        // 重新加载表格
        table.reload('questions-table', {
            where: {
                bank_id: bank_id || 0,
                type: type || 0
            }
        });
    });
    
    // 重置按钮点击事件
    $('#reset-btn').on('click', function() {
        $('#search-bank').val('0');
        $('#search-type').val('0');
        form.render('select');
        
        // 重新加载表格
        table.reload('questions-table', {
            where: {
                bank_id: 0,
                type: 0
            }
        });
    });
    
    // 编辑题目
    function editQuestion(question) {
        // 隐藏题目列表，显示编辑表单
        $('#questions').hide();
        $('#add-question').show();
        
        // 加载题库选项
        loadBankOptions();
        
        // 填充表单数据
        $('input[name="id"]').val(question.id);
        $('select[name="bank_id"]').val(question.bank_id);
        $('select[name="type"]').val(question.type);
        $('select[name="difficulty"]').val(question.difficulty);
        $('textarea[name="content"]').val(question.content);
        
        // 根据题目类型填充答案
        if(question.type === 4 || question.type === 5) {
            // 填空题、简答题
            $('textarea[name="non_choice_answer"]').val(question.answer);
        } else {
            // 选择题和判断题，使用完全一样的UI
            $('input[name="answer"]').val(question.answer);
            
            // 解析选项 - 所有选择题类型都使用相同的UI
            var options = question.options.split('|');
            var container = $('.options-container');
            container.html('');
            
            options.forEach(function(option, index) {
                var letter = String.fromCharCode(65 + index);
                var isCorrect = question.answer.includes(letter) ? 'checked' : '';
                
                var optionHtml = '<div class="option-item" style="margin-bottom: 10px;">' +
                    '<div class="layui-row">' +
                        '<div class="layui-col-md1">' +
                            '<div class="layui-form-mid layui-word-aux" style="font-weight: bold; font-size: 16px;">' + letter + '</div>' +
                            '<input type="hidden" name="options[' + index + '][letter]" value="' + letter + '">' +
                        '</div>' +
                        '<div class="layui-col-md10">' +
                            '<input type="text" name="options[' + index + '][content]" value="' + option + '" placeholder="选项内容" class="layui-input" style="margin-bottom: 5px;" lay-verify="requiredOption">' +
                        '</div>' +
                        '<div class="layui-col-md1">' +
                            '<input type="checkbox" name="options[' + index + '][is_correct]" lay-skin="primary" title="正确" ' + isCorrect + '>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                
                container.append(optionHtml);
            });
        }
        
        // 填充解析
        $('textarea[name="analysis"]').val(question.analysis);
        
        // 触发类型变化事件，显示正确的输入区域
        $('select[name="type"]').trigger('change');
        
        // 重新渲染表单
        form.render();
        
        // 绑定选项事件
        bindOptionEvents();
        
        // 同步单条输入到批量输入
        syncSingleToBatch();
        
        // 同步答案到复选框
        syncAnswerToCheckbox();
    }
    
    // 删除题目
    function deleteQuestion(id) {
        layer.confirm('确定要删除这个题目吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            $.ajax({
                url: 'api/questions.php?id=' + id,
                method: 'DELETE',
                dataType: 'json',
                success: function(res) {
                    if(res.code === 200) {
                        layer.msg(res.msg, {icon: 1});
                        // 重新加载表格
                        table.reload('questions-table');
                    } else {
                        layer.msg(res.msg, {icon: 2});
                    }
                },
                error: function(xhr, status, error) {
                    layer.msg('删除失败: ' + error, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    }
    
    // 监听题目表单提交
    form.on('submit(submit-question)', function(data) {
        var questionData = data.field;
        var url = 'api/questions.php';
        var method = questionData.id ? 'PUT' : 'POST';
        
        // 前端验证
        if(questionData.type === '3' || questionData.type === '4' || questionData.type === '5') {
            // 判断题、填空题、简答题需要验证答案
            if(!questionData.non_choice_answer || questionData.non_choice_answer.trim() === '') {
                layer.msg('答案不能为空', {icon: 2});
                return false;
            }
            // 将非选择题答案赋值给answer字段
            questionData.answer = questionData.non_choice_answer;
            // 删除临时字段
            delete questionData.non_choice_answer;
        }
        
        // 处理选项数据
        if(questionData.type === '1' || questionData.type === '2' || questionData.type === '3') {
            var options = [];
            var hasCorrectAnswer = false;
            
            // 获取所有选项
            $('.option-item').each(function(index) {
                var optionItem = $(this);
                var content = optionItem.find('input[name^="options[' + index + '][content]"]').val();
                var isCorrect = optionItem.find('input[name^="options[' + index + '][is_correct]"]').is(':checked') ? 1 : 0;
                
                if(isCorrect) {
                    hasCorrectAnswer = true;
                }
                
                if(content) {
                    options.push(content);
                }
            });
            
            // 验证选择题必须有正确答案
            if(!hasCorrectAnswer) {
                layer.msg('选择题必须至少有一个正确答案', {icon: 2});
                return false;
            }
            
            // 生成选项字符串，只保存选项内容，用竖线分隔
            questionData.options = options.join('|');
            
            // 验证正确答案是否为字母格式
            if(!questionData.answer || questionData.answer.trim() === '') {
                layer.msg('正确答案不能为空', {icon: 2});
                return false;
            }
            
            // 验证答案只包含字母
            var answerPattern = /^[A-Za-z]+$/;
            if(!answerPattern.test(questionData.answer)) {
                layer.msg('正确答案只能包含字母（如：A、AC、B）', {icon: 2});
                return false;
            }
        }
        
        // 提交时自动检测并修改为相对应的题型
        if(questionData.type === '1' || questionData.type === '2') {
            var answer = questionData.answer.toUpperCase();
            var checkedCount = $('.option-item input[name^="options["][name$="][is_correct]"]:checked').length;
            
            // 当答案长度>1或选中数量>1时，自动切换为多选
            if(answer.length > 1 || checkedCount > 1) {
                questionData.type = '2'; // 多选
            } else {
                questionData.type = '1'; // 单选
            }
        }
        
        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(questionData),
            dataType: 'json',
            success: function(res) {
                if(res.code === 200 || res.code === 201) {
                    layer.msg(res.msg, {icon: 1});
                    // 返回列表并刷新
                    $('#add-question').hide();
                    $('#questions').show();
                    table.reload('questions-table');
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function(xhr, status, error) {
                layer.msg('操作失败: ' + error, {icon: 2});
            }
        });
        
        return false;
    });
    
    // 题库管理相关功能
    // 初始化题库表格
    function initBanksTable() {
        table.render({
            elem: '#banks-table',
            url: 'api/banks.php',
            method: 'GET',
            page: false,
            limit: 20,
            height: 'full-150',
            request: {
                pageName: 'page', // 页码的参数名称，默认：page
                limitName: 'limit' // 每页数据量的参数名，默认：limit
            },
            response: {
                statusName: 'code', // 规定数据状态的字段名称，默认：code
                statusCode: 200, // 规定成功的状态码，默认：0
                msgName: 'msg', // 规定状态信息的字段名称，默认：msg
                countName: 'count', // 规定数据总数的字段名称，默认：count
                dataName: 'data' // 规定数据列表的字段名称，默认：data
            },
            cols: [[
                {field: 'id', title: 'ID', width: 80, align: 'center'},
                {field: 'name', title: '题库名称', minWidth: 200},
                {field: 'description', title: '题库描述', minWidth: 300},
                {field: 'category_id', title: '分类ID', width: 100, align: 'center'},
                {field: 'status', title: '状态', width: 100, align: 'center', templet: '<div>{{d.status === 1 ? "启用" : "禁用"}}</div>'},
                {field: 'created_at', title: '创建时间', width: 160, align: 'center'},
                {field: 'updated_at', title: '更新时间', width: 160, align: 'center'},
                {fixed: 'right', title: '操作', width: 160, align: 'center', toolbar: '#banks-toolbar'}
            ]],
            done: function(res) {
                if(res.code === 200) {
                    // 加载题库选项到下拉框
                    loadBankOptions(res.data);
                }
            }
        });
        
        // 绑定工具栏事件
        table.on('tool(banks-filter)', function(obj) {
            var data = obj.data;
            if(obj.event === 'edit') {
                // 编辑题库
                editBank(data);
            } else if(obj.event === 'del') {
                // 删除题库
                deleteBank(data.id);
            }
        });
    }
    
    // 加载题库选项到下拉框
    function loadBankOptions(banks) {
        var select = $('select[name="bank_id"]');
        // 如果没有传入banks，从服务器获取
        if(!banks) {
            $.ajax({
                url: 'api/banks.php',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    if(res.code === 200) {
                        loadBankOptions(res.data);
                    }
                }
            });
            return;
        }
        
        // 清空现有选项
        select.find('option:not([value="0"])').remove();
        
        // 添加题库选项
        banks.forEach(function(bank) {
            select.append('<option value="' + bank.id + '">' + bank.name + '</option>');
        });
        
        // 重新渲染表单
        form.render('select');
    }
    
    // 添加题库按钮点击事件
    $('#add-bank-btn').on('click', function() {
        // 显示添加题库表单
        $('#banks').hide();
        $('#add-bank').show();
    });
    
    // 返回题库列表按钮点击事件
    $('#back-to-banks').on('click', function() {
        // 显示题库列表
        $('#add-bank').hide();
        $('#banks').show();
        // 重新加载表格
        initBanksTable();
    });
    
    // 编辑题库
    function editBank(bank) {
        // 显示添加题库表单
        $('#banks').hide();
        $('#add-bank').show();
        
        // 填充表单数据
        $('input[name="id"]').val(bank.id);
        $('input[name="name"]').val(bank.name);
        $('textarea[name="description"]').val(bank.description);
        $('select[name="category_id"]').val(bank.category_id);
        
        // 重新渲染表单
        form.render();
    }
    
    // 删除题库
    function deleteBank(id) {
        layer.confirm('确定要删除这个题库吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            $.ajax({
                url: 'api/banks.php?id=' + id,
                method: 'DELETE',
                dataType: 'json',
                success: function(res) {
                    if(res.code === 200) {
                        layer.msg(res.msg, {icon: 1});
                        // 重新加载表格
                        table.reload('banks-table');
                    } else {
                        layer.msg(res.msg, {icon: 2});
                    }
                },
                error: function(xhr, status, error) {
                    layer.msg('删除失败: ' + error, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    }
    
    // 监听题库表单提交
    form.on('submit(submit-bank)', function(data) {
        var bankData = data.field;
        var url = 'api/banks.php';
        var method = bankData.id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(bankData),
            dataType: 'json',
            success: function(res) {
                if(res.code === 200 || res.code === 201) {
                    layer.msg(res.msg, {icon: 1});
                    // 返回列表并刷新
                    $('#add-bank').hide();
                    $('#banks').show();
                    table.reload('banks-table');
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function(xhr, status, error) {
                layer.msg('操作失败: ' + error, {icon: 2});
            }
        });
        
        return false;
    });
    
    // 数据导入导出相关功能
    // 文件上传功能
    function bindUploadEvents() {
        console.log('绑定文件上传事件');
        
        // 为所有上传按钮绑定事件
        $('.card').on('click', '#upload-btn', function() {
            console.log('点击了选择文件按钮');
            var fileInput = $(this).siblings('#file-upload');
            console.log('找到文件输入框:', fileInput.length);
            if(fileInput.length > 0) {
                fileInput.click();
            } else {
                console.error('未找到文件输入框');
            }
        });
        
        // 为所有文件输入框绑定事件
        $('.card').on('change', '#file-upload', function(e) {
            console.log('文件选择事件触发');
            var file = e.target.files[0];
            if(!file) return;
            
            console.log('选择的文件:', file.name);
            
            // 显示文件名
            var fileName = file.name;
            $(this).siblings('#file-name').text('已选择文件: ' + fileName);
            
            // 检查文件类型
            var fileExt = fileName.toLowerCase().split('.').pop();
            console.log('文件类型:', fileExt);
            if(fileExt === 'txt') {
                // 读取txt文件
                readTxtFile(file);
            } else if(fileExt === 'xlsx') {
                // 读取excel文件
                readExcelFile(file);
            } else {
                layer.msg('不支持的文件类型，仅支持txt和xlsx文件', {icon: 2});
            }
        });
    }
    
    // 读取txt文件
    function readTxtFile(file) {
        console.log('开始读取txt文件');
        var reader = new FileReader();
        reader.onload = function(e) {
            console.log('文件读取完成');
            var content = e.target.result;
            console.log('文件内容长度:', content.length);
            $('textarea[name="import_data"]').val(content);
            layer.msg('文件读取成功', {icon: 1});
        };
        reader.onerror = function() {
            console.error('文件读取失败');
            layer.msg('文件读取失败', {icon: 2});
        };
        reader.readAsText(file, 'utf-8');
    }
    
    // 读取excel文件
    function readExcelFile(file) {
        console.log('尝试读取excel文件');
        // 这里需要引入xlsx库来解析excel文件
        // 由于当前项目没有引入该库，暂时提示用户
        layer.msg('Excel文件解析需要额外的库支持，请先使用txt文件导入', {icon: 2});
    }
    
    // 在模块加载完成后绑定事件
    $(document).on('click', '#upload-btn', function() {
        console.log('直接事件绑定触发');
        $(this).siblings('#file-upload').click();
    });
    
    $(document).on('change', '#file-upload', function(e) {
        console.log('直接文件选择事件触发');
        var file = e.target.files[0];
        if(!file) return;
        
        var fileName = file.name;
        $(this).siblings('#file-name').text('已选择文件: ' + fileName);
        
        var fileExt = fileName.toLowerCase().split('.').pop();
        if(fileExt === 'txt') {
            readTxtFile(file);
        } else if(fileExt === 'xlsx') {
            readExcelFile.call(this, file);
        } else {
            layer.msg('不支持的文件类型，仅支持txt和xlsx文件', {icon: 2});
        }
    });
    
    // 解析数据按钮点击事件 - 使用事件委托
    $(document).on('click', '#parse-btn', function() {
        // 解析导入数据
        parseImportData();
    });
    
    // 导入数据按钮点击事件 - 使用事件委托
    $(document).on('click', '#import-btn', function() {
        // 导入数据
        importData();
    });
    
    // 初始化模块加载逻辑
    // 导航点击事件
    element.on('nav(admin-nav)', function(elem) {
        // 获取lay-href属性
        var href = elem.attr('lay-href');
        if(href && href.startsWith('#')) {
            // 获取模块名称（去掉#）
            var moduleName = href.substring(1);
            
            // 加载对应模块的HTML文件
            $.ajax({
                url: 'modules/' + moduleName + '.html',
                method: 'GET',
                success: function(html) {
                    // 清空内容区域
                    $('#module-content').html('');
                    // 添加新模块内容
                    $('#module-content').html(html);
                    
                    // 如果是分类管理，初始化表格
                    if(moduleName === 'categories') {
                        initCategoriesTable();
                    }
                    // 如果是题目管理，初始化表格
                    else if(moduleName === 'questions') {
                        initQuestionsTable();
                    }
                    // 如果是题库管理，初始化表格
                    else if(moduleName === 'banks') {
                        initBanksTable();
                    }
                    // 如果是错题/收藏管理，初始化表格
                    else if(moduleName === 'wrong-questions') {
                        initWrongQuestionsTable();
                    }
                    // 如果是错题统计，初始化统计
                    else if(moduleName === 'wrong-stats') {
                        // 初始化统计数据
                        initWrongStats();
                    }
                    // 如果是添加题目，初始化选项事件
                    else if(moduleName === 'add-question') {
                        // 初始化选项事件
                        initOptionEvents();
                    }
                    // 如果是导入数据，初始化上传事件
                    else if(moduleName === 'import-data') {
                        // 初始化上传事件
                        bindUploadEvents();
                    }
                },
                error: function(xhr, status, error) {
                    layer.msg('加载模块失败: ' + error, {icon: 2});
                }
            });
        }
    });
    
    // 初始加载仪表盘
    $.ajax({
        url: 'modules/dashboard.html',
        method: 'GET',
        success: function(html) {
            $('#module-content').html(html);
        },
        error: function(xhr, status, error) {
            layer.msg('加载仪表盘失败: ' + error, {icon: 2});
        }
    });
    
    // 解析导入数据
    function parseImportData() {
        var data = $('textarea[name="import_data"]').val();
        if(!data) {
            layer.msg('请输入要导入的数据', {icon: 2});
            return;
        }
        
        // 解析数据
        var lines = data.split('\n');
        var result = [];
        
        lines.forEach(function(line, index) {
            if(!line.trim()) return;
            
            var parts = line.split('\t');
            if(parts.length < 6) {
                layer.msg('第' + (index + 1) + '行格式错误', {icon: 2});
                return;
            }
            
            var typeMap = {
                '单选': 1,
                '多选': 2,
                '判断': 3,
                '填空': 4,
                '简答': 5
            };
            
            result.push({
                index: parts[0],
                type: parts[1],
                type_id: typeMap[parts[1]],
                content: parts[2],
                options: parts[3],
                answer: parts[4],
                analysis: parts[5]
            });
        });
        
        // 显示解析结果
        showParseResult(result);
    }
    
    // 显示解析结果
    function showParseResult(result) {
        var tbody = $('#parse-result-body');
        tbody.html('');
        
        result.forEach(function(item) {
            var tr = '<tr>' +
                '<td>' + item.index + '</td>' +
                '<td>' + item.type + '</td>' +
                '<td>' + item.content + '</td>' +
                '<td>' + item.options + '</td>' +
                '<td>' + item.answer + '</td>' +
                '<td>' + item.analysis + '</td>' +
            '</tr>';
            tbody.append(tr);
        });
        
        $('#parse-result').show();
        $('#parse-status').show().text('解析完成，共' + result.length + '条记录');
    }
    
    // 导入数据
    function importData() {
        var data = $('textarea[name="import_data"]').val();
        if(!data) {
            layer.msg('请输入要导入的数据', {icon: 2});
            return;
        }
        
        // 发送导入请求
        $.ajax({
            url: 'api/import.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({data: data}),
            dataType: 'json',
            success: function(res) {
                if(res.code === 200) {
                    layer.msg('导入成功', {icon: 1});
                    // 清空输入框
                    $('textarea[name="import_data"]').val('');
                    // 隐藏解析结果
                    $('#parse-result').hide();
                    $('#parse-status').hide();
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function(xhr, status, error) {
                layer.msg('导入失败: ' + error, {icon: 2});
            }
        });
    }
    
    // 初始加载
    loadStats();
});