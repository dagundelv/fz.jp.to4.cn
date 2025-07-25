    </main><?php // 主要内容区域结束 ?>
    
    <footer class="main-footer">
        <div class="footer-content">
            <div class="container">
                <div class="footer-sections">
                    <!-- 网站信息 -->
                    <div class="footer-section">
                        <h3>关于我们</h3>
                        <p class="footer-description">
                            <?php echo h(SITE_NAME); ?>是专业的健康医疗信息平台，致力于为用户提供准确、及时的医疗健康资讯和服务。
                        </p>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>客服热线：400-123-4567</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>邮箱：support@health.com</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock"></i>
                                <span>服务时间：周一至周日 8:00-22:00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 快速导航 -->
                    <div class="footer-section">
                        <h3>快速导航</h3>
                        <ul class="footer-links">
                            <li><a href="/">网站首页</a></li>
                            <li><a href="/news/">健康头条</a></li>
                            <li><a href="/hospitals/">医院查询</a></li>
                            <li><a href="/doctors/">医生查询</a></li>
                            <li><a href="/diseases/">疾病百科</a></li>
                            <li><a href="/qa/">健康问答</a></li>
                        </ul>
                    </div>
                    
                    <!-- 用户服务 -->
                    <div class="footer-section">
                        <h3>用户服务</h3>
                        <ul class="footer-links">
                            <li><a href="/user/register.php">用户注册</a></li>
                            <li><a href="/user/login.php">用户登录</a></li>
                            <li><a href="/help/appointment.php">预约挂号</a></li>
                            <li><a href="/help/feedback.php">意见反馈</a></li>
                            <li><a href="/help/privacy.php">隐私政策</a></li>
                            <li><a href="/help/terms.php">使用条款</a></li>
                        </ul>
                    </div>
                    
                    <!-- 热门科室 -->
                    <div class="footer-section">
                        <h3>热门科室</h3>
                        <ul class="footer-links">
                            <?php
                            $popularCategories = getCategories(0);
                            foreach (array_slice($popularCategories, 0, 6) as $category):
                            ?>
                            <li>
                                <a href="/doctors/?category=<?php echo $category['id']; ?>">
                                    <?php echo h($category['name']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- 关注我们 -->
                    <div class="footer-section">
                        <h3>关注我们</h3>
                        <div class="social-links">
                            <a href="#" class="social-link" title="微信公众号">
                                <i class="fab fa-weixin"></i>
                            </a>
                            <a href="#" class="social-link" title="新浪微博">
                                <i class="fab fa-weibo"></i>
                            </a>
                            <a href="#" class="social-link" title="QQ群">
                                <i class="fab fa-qq"></i>
                            </a>
                        </div>
                        
                        <!-- 统计数据 -->
                        <div class="site-stats">
                            <?php $stats = getSiteStats(); ?>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['hospitals']); ?></span>
                                    <span class="stat-label">合作医院</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['doctors']); ?></span>
                                    <span class="stat-label">专业医生</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['users']); ?></span>
                                    <span class="stat-label">注册用户</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['questions']); ?></span>
                                    <span class="stat-label">问答数量</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 底部版权信息 -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <div class="copyright">
                        <p>&copy; <?php echo date('Y'); ?> <?php echo h(SITE_NAME); ?>. 版权所有</p>
                        <p>
                            <a href="/help/privacy.php">隐私政策</a>
                            <span class="separator">|</span>
                            <a href="/help/terms.php">使用条款</a>
                            <span class="separator">|</span>
                            <a href="/help/contact.php">联系我们</a>
                        </p>
                    </div>
                    
                    <div class="footer-disclaimer">
                        <p class="disclaimer-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            本站所有医疗信息仅供参考，不能替代专业医生的诊断和治疗建议，如有疑虑请及时就医。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- 返回顶部按钮 -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </div>
    
    <!-- 在线客服 -->
    <div class="online-service">
        <div class="service-btn" id="serviceBtn">
            <i class="fas fa-comments"></i>
            <span>在线客服</span>
        </div>
        <div class="service-panel" id="servicePanel">
            <div class="service-header">
                <span>在线客服</span>
                <button class="close-service">×</button>
            </div>
            <div class="service-content">
                <div class="service-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <strong>电话咨询</strong>
                        <p>400-123-4567</p>
                    </div>
                </div>
                <div class="service-item">
                    <i class="fab fa-qq"></i>
                    <div>
                        <strong>QQ咨询</strong>
                        <p>123456789</p>
                    </div>
                </div>
                <div class="service-item">
                    <i class="fab fa-weixin"></i>
                    <div>
                        <strong>微信咨询</strong>
                        <p>扫描二维码</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 全局JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/search.js"></script>
    
    <!-- 页面特定JavaScript -->
    <?php if (isset($pageJS)): ?>
        <?php foreach ($pageJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
    // 全局配置
    window.SITE_CONFIG = {
        baseUrl: '<?php echo SITE_URL; ?>',
        currentUser: <?php echo $currentUser ? json_encode($currentUser, JSON_UNESCAPED_UNICODE) : 'null'; ?>,
        isLoggedIn: <?php echo isLoggedIn() ? 'true' : 'false'; ?>
    };
    </script>
</body>
</html>