<?php
/**
 * Plugin Name:       Snippets For Pages WP (HTML/CSS/JS)
 * Description:       Com este plugin você pode criar páginas completas ou snippets de código de forma 
 *                    organizada e centralizada usando códigos HTML, CSS e JavaScript puros sem a interferência do tema ativo.
 * Version:           1.0
 * Author:            Leonardo Delfino
 */

// Se não for acessado pelo WordPress, saia.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CustomHTMLPages {

    public function __construct() {
        // Hooks de ativação/desativação para lidar com as regras de URL
        register_activation_hook( __FILE__, [ $this, 'plugin_activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'plugin_deactivate' ] );

        // Ações e Filtros principais do plugin
        add_action( 'init', [ $this, 'register_custom_post_type' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_custom_meta_box' ] );
        add_action( 'save_post_codigo_page', [ $this, 'save_custom_meta_box_data' ] );
        add_filter( 'template_include', [ $this, 'override_template' ] );

        // Hook para carregar os scripts e estilos do CodeMirror
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_codemirror_assets' ] );
    }

    /**
     * Ação na ativação do plugin: Registra o CPT e limpa as regras de reescrita.
     */
    public function plugin_activate() {
        $this->register_custom_post_type();
        flush_rewrite_rules(); // Essencial para evitar o erro 404
    }

    /**
     * Ação na desativação do plugin: Limpa as regras para não deixar lixo.
     */
    public function plugin_deactivate() {
        flush_rewrite_rules();
    }

    /**
     * 1. Registra o Custom Post Type "Página de Código"
     */
    public function register_custom_post_type() {
        // ... (o código de register_custom_post_type continua o mesmo da versão anterior)
        $labels = [
            'name'                  => _x( 'Páginas de Código', 'Post Type General Name', 'chp' ),
            'singular_name'         => _x( 'Página de Código', 'Post Type Singular Name', 'chp' ),
            'menu_name'             => __( 'Páginas de Código', 'chp' ),
            'name_admin_bar'        => __( 'Página de Código', 'chp' ),
            'add_new_item'          => __( 'Criar Nova Página de Código', 'chp' ),
            'add_new'               => __( 'Criar Nova', 'chp' ),
            'new_item'              => __( 'Nova Página', 'chp' ),
            'edit_item'             => __( 'Editar Página de Código', 'chp' ),
            'update_item'           => __( 'Atualizar Página de Código', 'chp' ),
            'view_item'             => __( 'Ver Página de Código', 'chp' ),
            'search_items'          => __( 'Procurar Página de Código', 'chp' ),
        ];
        $args = [
            'label'                 => __( 'Página de Código', 'chp' ),
            'description'           => __( 'Páginas criadas com código HTML/CSS/JS puro', 'chp' ),
            'labels'                => $labels,
            'supports'              => [ 'title' ],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-editor-code',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'rewrite'               => array('slug' => 'p'),
        ];
        register_post_type( 'codigo_page', $args );
    }
    
    /**
     * 2. Adiciona o menu ao painel de Admin
     */
    public function add_admin_menu() {
        // ... (o código de add_admin_menu continua o mesmo)
        add_menu_page(
            'Páginas de Código',
            'Páginas de Código',
            'manage_options',
            'edit.php?post_type=codigo_page',
            '',
            'dashicons-editor-code',
            20
        );
        add_submenu_page(
            'edit.php?post_type=codigo_page',
            'Criar Nova Página',
            'Criar Nova',
            'manage_options',
            'post-new.php?post_type=codigo_page'
        );
    }
    
    /**
     * Carrega os scripts e estilos do CodeMirror SOMENTE na página de edição.
     */
    public function enqueue_codemirror_assets($hook) {
        // Garante que os scripts só sejam carregados na página de edição do nosso CPT
        if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
            return;
        }
        if ( 'codigo_page' !== get_post_type() ) {
            return;
        }

        // Carrega o CSS do CodeMirror e um tema (ex: dracula)
        wp_enqueue_style( 'codemirror-css', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.css' );
        wp_enqueue_style( 'codemirror-theme-dracula', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.css' );

        // Carrega o JS do CodeMirror
        wp_enqueue_script( 'codemirror-js', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.js', [], false, true );

        // Carrega os modos de linguagem (HTML, CSS, JS)
        wp_enqueue_script( 'codemirror-mode-xml', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.js', ['codemirror-js'], false, true );
        wp_enqueue_script( 'codemirror-mode-css', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.js', ['codemirror-js'], false, true );
        wp_enqueue_script( 'codemirror-mode-js', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.js', ['codemirror-js'], false, true );
    }

    /**
     * 3. Adiciona a Meta Box com as abas de código
     */
    public function add_custom_meta_box() {
        remove_post_type_support('codigo_page', 'editor');
        add_meta_box(
            'codigo_editor_box',
            'Editor de Código',
            [ $this, 'render_meta_box_content' ],
            'codigo_page',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderiza o conteúdo da nossa Meta Box (agora com IDs para o CodeMirror)
     */
    public function render_meta_box_content($post) {
        wp_nonce_field( 'codigo_page_nonce', 'codigo_page_nonce_field' );

        $html_code = get_post_meta( $post->ID, '_html_code', true );
        $css_code = get_post_meta( $post->ID, '_css_code', true );
        $js_code = get_post_meta( $post->ID, '_js_code', true );
        ?>
        <style>
            .code-tabs { display: flex; border-bottom: 1px solid #ccc; }
            .tab-link { padding: 10px 15px; cursor: pointer; background: #f0f0f1; border: 1px solid #ccc; border-bottom: 0; margin-bottom: -1px; }
            .tab-link.active { background: #fff; border-top: 2px solid #2271b1; }
            .tab-content { display: none; border: 1px solid #ccc; border-top: 0; }
            .tab-content.active { display: block; }
            /* Ajustes para o CodeMirror dentro do admin */
            .CodeMirror { height: 500px; border: 1px solid #ddd; }
        </style>

        <div class="code-tabs">
            <div class="tab-link active" onclick="openTab(event, 'html')">HTML</div>
            <div class="tab-link" onclick="openTab(event, 'css')">CSS</div>
            <div class="tab-link" onclick="openTab(event, 'js')">JavaScript</div>
        </div>

        <div id="html" class="tab-content active">
            <textarea name="html_code" id="html_editor"><?php echo esc_textarea($html_code); ?></textarea>
        </div>
        <div id="css" class="tab-content">
            <textarea name="css_code" id="css_editor"><?php echo esc_textarea($css_code); ?></textarea>
        </div>
        <div id="js" class="tab-content">
            <textarea name="js_code" id="js_editor"><?php echo esc_textarea($js_code); ?></textarea>
        </div>

        <script>
            function openTab(evt, tabName) {
                let i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tab-link");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.className += " active";

                // Atualiza o editor CodeMirror quando a aba se torna visível
                if (window[tabName + '_instance']) {
                    window[tabName + '_instance'].refresh();
                }
            }

            // Inicializa o CodeMirror quando o DOM estiver pronto
            document.addEventListener("DOMContentLoaded", function() {
                window.html_instance = CodeMirror.fromTextArea(document.getElementById("html_editor"), {
                    lineNumbers: true,
                    mode: "xml",
                    theme: "dracula",
                    lineWrapping: true
                });
                window.css_instance = CodeMirror.fromTextArea(document.getElementById("css_editor"), {
                    lineNumbers: true,
                    mode: "css",
                    theme: "dracula",
                    lineWrapping: true
                });
                window.js_instance = CodeMirror.fromTextArea(document.getElementById("js_editor"), {
                    lineNumbers: true,
                    mode: "javascript",
                    theme: "dracula",
                    lineWrapping: true
                });
            });
        </script>
        <?php
    }

    /**
     * 4. Salva os dados dos nossos campos customizados
     */
    public function save_custom_meta_box_data($post_id) {
        // ... (o código de save_custom_meta_box_data continua o mesmo)
        if ( ! isset( $_POST['codigo_page_nonce_field'] ) || ! wp_verify_nonce( $_POST['codigo_page_nonce_field'], 'codigo_page_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( isset( $_POST['html_code'] ) ) {
            update_post_meta( $post_id, '_html_code', $_POST['html_code'] );
        }
        if ( isset( $_POST['css_code'] ) ) {
            update_post_meta( $post_id, '_css_code', $_POST['css_code'] );
        }
        if ( isset( $_POST['js_code'] ) ) {
            update_post_meta( $post_id, '_js_code', $_POST['js_code'] );
        }
    }
    
    /**
     * 5. Sobrescreve o template do tema
     */
    public function override_template($template) {
        // ... (o código de override_template continua o mesmo)
        if ( is_singular( 'codigo_page' ) ) {
            $plugin_template = plugin_dir_path( __FILE__ ) . 'page-template.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }
}

new CustomHTMLPages();
