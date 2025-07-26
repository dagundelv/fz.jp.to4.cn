            </div>
        </main>
    </div>
    
    <!-- 消息提示 -->
    <div id="adminMessage" class="admin-message" style="display: none;">
        <div class="message-content">
            <span class="message-text"></span>
            <button class="message-close" onclick="hideAdminMessage()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- 确认对话框 -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>确认操作</h3>
                <button class="modal-close" onclick="hideConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">您确定要执行此操作吗？</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideConfirmModal()">取消</button>
                <button class="btn btn-danger" id="confirmButton">确认</button>
            </div>
        </div>
    </div>
    
    <script>
    // 全局管理员JS函数
    function showAdminMessage(message, type = 'info') {
        const messageEl = document.getElementById('adminMessage');
        const textEl = messageEl.querySelector('.message-text');
        
        messageEl.className = 'admin-message show ' + type;
        textEl.textContent = message;
        messageEl.style.display = 'block';
        
        setTimeout(() => {
            hideAdminMessage();
        }, 5000);
    }
    
    function hideAdminMessage() {
        const messageEl = document.getElementById('adminMessage');
        messageEl.style.display = 'none';
    }
    
    function showConfirmModal(message, callback) {
        const modal = document.getElementById('confirmModal');
        const messageEl = document.getElementById('confirmMessage');
        const confirmBtn = document.getElementById('confirmButton');
        
        messageEl.textContent = message;
        modal.style.display = 'block';
        
        confirmBtn.onclick = function() {
            hideConfirmModal();
            if (callback) callback();
        };
    }
    
    function hideConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }
    
    // 页面加载完成后的初始化
    $(document).ready(function() {
        // AJAX表单提交
        $('form[data-ajax]').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = new FormData(this);
            const submitBtn = form.find('[type="submit"]');
            const originalText = submitBtn.text();
            
            submitBtn.prop('disabled', true).text('处理中...');
            
            $.ajax({
                url: form.attr('action'),
                method: form.attr('method') || 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAdminMessage(response.message, 'success');
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else if (response.reload) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    } else {
                        showAdminMessage(response.message, 'error');
                    }
                },
                error: function() {
                    showAdminMessage('操作失败，请稍后重试', 'error');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // 删除确认
        $('.delete-btn').on('click', function(e) {
            e.preventDefault();
            
            const url = $(this).attr('href');
            const message = $(this).data('confirm') || '您确定要删除此项吗？此操作不可撤销。';
            
            showConfirmModal(message, function() {
                window.location.href = url;
            });
        });
        
        // 批量操作
        $('#selectAll').on('change', function() {
            $('.item-checkbox').prop('checked', $(this).prop('checked'));
            updateBatchActions();
        });
        
        $('.item-checkbox').on('change', function() {
            updateBatchActions();
        });
        
        function updateBatchActions() {
            const checkedCount = $('.item-checkbox:checked').length;
            $('.batch-actions').toggle(checkedCount > 0);
            $('.selected-count').text(checkedCount);
        }
    });
    </script>
</body>
</html>