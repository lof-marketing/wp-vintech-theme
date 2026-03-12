<?php
/**
 * Vintech Child Theme Functions
 */

add_action( 'wp_enqueue_scripts', 'vintech_child_enqueue_styles', 20 );

function vintech_child_enqueue_styles() {
    // Cargar la hoja de estilos del tema padre
    wp_enqueue_style( 'vintech-parent-style', get_template_directory_uri() . '/style.css' );
    
    // Cargar la hoja de estilos del tema hijo
    wp_enqueue_style( 'vintech-child-style', 
        get_stylesheet_directory_uri() . '/style.css', 
        array( 'vintech-parent-style' ), 
        wp_get_theme()->get('Version') 
    );
}

/**
 * Configuración maestra de SMTP para Gmail
 * Esto sobrescribe cualquier otra configuración de correo en el sitio.
 */
add_action( 'phpmailer_init', 'asistente_configurar_smtp_gmail' );

function asistente_configurar_smtp_gmail( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 587; // Puerto estándar para TLS
    $phpmailer->SMTPSecure = 'tls'; 
    
    // REEMPLAZA ESTOS DATOS:
    $phpmailer->Username   = 'manuel@lofmarketing.com'; 
    $phpmailer->Password   = 'ewzv lunn yixm dupm'; // Tu Contraseña de Aplicación de 16 caracteres
    
    // Configuración del remitente (debe ser el mismo correo)
    $phpmailer->From       = 'manuel@lofmarketing.com';
    $phpmailer->FromName   = 'Diprotec';
}

add_action('wp_footer', function() {
    if (!is_checkout() && !is_cart()) return;
    ?>
    <script>
    jQuery(function($) {
        $(document.body).on('updated_checkout', function() {
            // Solo eliminar divs de nice-select duplicados si existen, sin reinicializar
            $('#shipping_method .nice-select:not(select)').remove();
            $('#shipping_method select.shipping_method').show();
        });
    });
    </script>
    <?php
});

if (!function_exists('vintech_wc_cart_totals_shipping_method_label')) {
    function vintech_wc_cart_totals_shipping_method_label($method) {
        $label    = $method->get_label();
        $packages = WC()->shipping()->get_packages();
        $price    = '';
        foreach ($packages as $package) {
            if (isset($package['rates'][$method->id])) {
                $price = wp_strip_all_tags(wc_price(
                    $package['rates'][$method->id]->cost +
                    $package['rates'][$method->id]->get_shipping_tax()
                ));
                break;
            }
        }
        return $price ? esc_html($label) . ': ' . $price : esc_html($label);
    }
}
// 1. Función para obtener el número real de productos
function obtener_cantidad_carrito_diprotec() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return '<span class="cart_total">0</span>';
    }
    $count = WC()->cart->get_cart_contents_count();
    return '<span class="cart_total">' . $count . '</span>';
}

// 2. Registramos el atajo (shortcode) para usar en Elementor
add_shortcode('cantidad_carrito_diprotec', 'obtener_cantidad_carrito_diprotec');

/**
 * TRADUCCIONES PERSONALIZADAS DIPROTEC
 * Este código intercepta los textos antes de que salgan a pantalla.
 */
add_filter( 'gettext', 'diprotec_custom_translations', 999, 3 );
function diprotec_custom_translations( $translated_text, $text, $domain ) {
    // Definimos las palabras a buscar y sus traducciones
    $traducir = array(
        'Remove'                    => 'Quitar',
        'remove'                    => 'Quitar',
        'Open Wishlist Page'        => 'Ver lista de deseos',
        'Continue Shopping'         => 'Continuar comprando',
        'Enter a different address' => 'Ingresa una dirección diferente',
		'Remove '                   => 'Quitar',
		'Wishlist'                  => 'Lista de deseos',
    );

    // Si la palabra exacta existe en nuestra lista, la cambiamos
    if ( isset( $traducir[$text] ) ) {
        return $traducir[$text];
    }

    return $translated_text;
}

// Forzar actualización y recálculo al seleccionar "Retiro en Tienda"
add_action( 'wp_footer', 'forzar_evento_select_envio' );
function forzar_evento_select_envio() {
    if ( is_checkout() || is_cart() ) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Escucha cambios en el select de envíos para recargar el carrito
                $(document.body).on('change', 'select.shipping_method', function() {
                    $('body').trigger('update_checkout');
                    $('body').trigger('wc_update_cart');
                });
            });
        </script>
        <?php
    }
}

/**
 * Forzar a WooCommerce a leer la plantilla de envíos desde el Tema Child
 * anulando la restricción del tema padre.
 */
add_filter( 'wc_get_template', 'priorizar_plantillas_tema_hijo', 20, 5 );

function priorizar_plantillas_tema_hijo( $template, $template_name, $args, $template_path, $default_path ) {
    
    // Si WooCommerce está buscando la plantilla de envíos...
    if ( $template_name === 'cart/cart-shipping.php' ) {
        
        // Construimos la ruta hacia tu Tema Child
        // get_stylesheet_directory() apunta al child theme, a diferencia de get_template_directory()
        $ruta_hijo = get_stylesheet_directory() . '/woocommerce/cart/pxl-cart-shipping.php';
        
        // Si el archivo existe en tu Tema Child, le obligamos a usarlo
        if ( file_exists( $ruta_hijo ) ) {
            return $ruta_hijo;
        }
    }
    
    // Para el resto de archivos, dejamos que siga su comportamiento normal
    return $template;
}