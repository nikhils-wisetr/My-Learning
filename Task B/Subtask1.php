Product types:
Simple       - A simple product is a physical product that is shipped to the customer. It has no variations or options.
Variable.    - Worked as simple but price my vary as per selection of attributes
Grouped.     - Subscription cannot be purchased as part of a grouped product 
External     - No cart /checkout, just a link to the product page
Subscription - Using WooCommerce Subscriptions plugin

<?php 

add_action('plugins_loaded', 'woocommerce_init', 0);
// WC()->init(); Will initialize the WooCommerce plugin and make its functions and features available for use. 
// This is typically used in custom code or themes that need to interact with WooCommerce functionality.

WC()->session = new WC_Session_Handler();
WC()->session->init();

// wp_woocommerce_session_<hash> //only for guest users, for logged in users it will be wp_woocommerce_session_<user_id>

// Request starts
//    ↓
// plugins_loaded
//    ↓
// init (priority 0)
//    → Session initialized
//    ↓
// init (priority 10)
//    → Cart initialized from session
//    ↓
// User interacts (add/remove cart)
//    ↓
// Session updated
//    ↓
// shutdown
//    → Session saved to DB ( For guest users, session data is stored in the wp_woocommerce_sessions table with a unique hash. 
//      For logged-in users, the session data is associated with their user ID.
//      This allows WooCommerce to retrieve and manage the session data for each user effectively. )
//    ↓
// CRON
//    → Expired sessions deleted ( every 12 hours by default we may increase this time to 24 hours or more depending on our needs)


// Why the Difference?
                     
//   Architecture shift. The shortcode checkout is monolithic PHP — the server renders the HTML,
//   handles validation, and creates the order. Hooks can be injected anywhere in that pipeline.
                                                                                                                                                                                                                                                              
//   The block checkout splits responsibilities:
//   - Frontend (React) → handles rendering, validation, UX                                                                                                                                                                                                      
//   - Backend (Store API) → handles order creation, payment                                                                                                                                                                                                     
                                                         
//   So:                                                                                                                                                                                                                                                         
//   1. PHP rendering hooks became useless — React renders the form, not PHP templates. You can't echo '<div>' into a React component.                                                                                                                           
//   2. JS extension points were needed — to let plugins add fields/UI in the React-rendered form.                                                                                                                                                               
//   3. New Store API hooks were needed — because the order creation goes through a different code path (StoreApi/Routes/V1/Checkout.php) instead of the legacy WC_Checkout class.                                                                               
//   4. Order-level hooks still work — because both paths ultimately call wc_create_order() and the payment gateway's process_payment().                                                                                                                                                                                                                                                   
                                                                                                                                                                                                                                                              
//   ┌────────────────────────┬─────────────────────────────────────────────────────────────────┬──────────────────────────────────┐
//   │                        │                            Shortcode                            │              Block               │
//   ├────────────────────────┼─────────────────────────────────────────────────────────────────┼──────────────────────────────────┤                                                                                                                             
//   │ Form rendered by       │ PHP                                                             │ React (JS)                       │
//   ├────────────────────────┼─────────────────────────────────────────────────────────────────┼──────────────────────────────────┤                                                                                                                             
//   │ Form customized via    │ PHP hooks/filters                                               │ JS slot fills + filters          │
//   ├────────────────────────┼─────────────────────────────────────────────────────────────────┼──────────────────────────────────┤                                                                                                                             
//   │ Order submitted via    │ ?wc_ajax=checkout                                               │ POST /wc/store/v1/checkout       │
//   ├────────────────────────┼─────────────────────────────────────────────────────────────────┼──────────────────────────────────┤                                                                                                                             
//   │ Order processing hooks │ woocommerce_checkout_*                                          │ woocommerce_store_api_checkout_* │
//   ├────────────────────────┼─────────────────────────────────────────────────────────────────┼──────────────────────────────────┤                                                                                                                             
//   │ Post-order hooks       │ Same (woocommerce_payment_complete, woocommerce_thankyou, etc.) │ Same                             │
//   └────────────────────────┴─────────────────────────────────────────────────────────────────┴──────────────────────────────────┘ 


// With HPOS (High-Performance Order Storage) enabled, WooCommerce orders are no longer stored as WP_Post objects in the wp_posts table.

// in older order 
// Orders stored in:
// wp_posts (post_type = shop_order)
// wp_postmeta
// $order could often be treated like a WP_Post

// HPOS (High-Performance Order Storage)
// Orders stored in custom tables like:
// wp_wc_orders
// wp_wc_order_addresses
// wp_wc_order_operational_data
// $order is now a WC_Order object backed by custom data stores, NOT a post

// wp_wc_orders                    ← main order record
// wp_wc_orders_meta               ← custom meta (like postmeta but only for orders)                                                                                                                                                                           
// wp_wc_order_addresses            ← billing + shipping addresses                                                                                                                                                                                             
// wp_wc_order_operational_data     ← payment, shipping, tax details
// wp_wc_order_items                ← (already existed)                                                                                                                                                                                                        
// wp_wc_order_itemmeta             ← (already existed)

// Order Meta ( on entire order, not individual items ) is now stored in wp_wc_orders_meta instead of wp_postmeta.
$order = wc_get_order( $order_id );
$order->get_meta( '_custom_note' );
$order->update_meta_data( '_custom_note', 'Hello' );
$order->save();

// "This order has 3 products → 3 line items"
$items = $order->get_items();

foreach ( $items as $item_id => $item ) {
    $product_id = $item->get_product_id();
    $quantity   = $item->get_quantity();
    $value     = $item->get_meta( 'engraving_text' );
}

// Order Meta	 Whole order	WC_Order	$order->get_meta()
// Line Items	 Products in order	    WC_Order_Item_Product	$order->get_items()
// Order Item   Meta	Per item	    WC_Order_Item_*	$item->get_meta()

// WooCommerce Customer Sessions — Complete Breakdown                                                                                                                                                                                                          
                                                                    
//   ---                                                                                                                                                                                                                                                         
//   Two Completely Different Systems                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                              
//   WooCommerce uses different session mechanisms for logged-in users vs guests:                                                                                                                                                                                
                                                                                                                                                                                                                                                              
//   Customer visits store
//           │                                                                                                                                                                                                                                                   
//           ├── Logged in? ──── YES ──→ WordPress User Meta (wp_usermeta)
//           │                           Session ID = User ID (e.g., 42)                                                                                                                                                                                         
//           │                                                                                                                                                                                                                                                   
//           └── Guest? ──────── YES ──→ WooCommerce Session Table (wp_woocommerce_sessions)                                                                                                                                                                     
//                                       Session ID = random 32-char hash                                                                                                                                                                                        
//                                       + cookie: wp_woocommerce_session_<hash>   


                                                                                                                                                                                                                                                              
// class WC_Session_Handler extends WC_Session {                                                                                                                                                                                                               
                                                                                                                                                                                                                                                              
//       public function init() {                                                                                                                                                                                                                                
//           // Read cookie
//           $this->_customer_id = $this->get_session_cookie();                                                                                                                                                                                                  
                                                                                                                                                                                                                                                              
//           if ($this->_customer_id) {
//               // Returning visitor — load existing session                                                                                                                                                                                                    
//               $this->_has_cookie = true;
//               $this->_data = $this->get_session_data();                                                                                                                                                                                                       
//           } else {
//               // Brand new visitor — generate session ID                                                                                                                                                                                                      
//               $this->_customer_id = $this->generate_customer_id();                                                                                                                                                                                            
//               $this->_data = array();
//           }                                                                                                                                                                                                                                                   
//       }           

//       private function generate_customer_id() {                                                                                                                                                                                                               
//           if (is_user_logged_in()) {
//               return (string) get_current_user_id();  // e.g., "42"                                                                                                                                                                                           
//           } else {                                                                                                                                                                                                                                            
//               // Random 32-character hex string
//               return wp_generate_password(32, false);  // e.g., "a1b2c3d4e5f6..."                                                                                                                                                                             
//           }       
//       }                                                                                                                                                                                                                                                       
//   }  

//   ---
//   Logged-In User Sessions
                                                                                                                                                                                                                                                              
//   Storage: wp_usermeta
                                                                                                                                                                                                                                                              
//   wp_usermeta     
//   ├── user_id | meta_key                         | meta_value                                                                                                                                                                                                 
//   ├── 42      | _woocommerce_persistent_cart_1    | {serialized cart data}
//   └── 42      | ...                                                                                                                                                                                                                                           
                                                                                                                                                                                                                                                              
//   How it works                                                                                                                                                                                                                                                
                                                                                                                                                                                                                                                              
//   User logs in    
//       │                                                                                                                                                                                                                                                       
//       ▼
//   Session ID = WordPress User ID (42)                                                                                                                                                                                                                         
//       │                                                                                                                                                                                                                                                       
//       ▼
//   Cart data stored in wp_usermeta as _woocommerce_persistent_cart_1                                                                                                                                                                                           
//       │           
//       ▼
//   Session data (non-cart) stored in wp_woocommerce_sessions
//   with customer_id = "42"                                                                                                                                                                                                                                     
//       │                                                                                                                                                                                                                                                       
//       ▼                                                                                                                                                                                                                                                       
//   No expiry cookie needed — tied to WordPress login session                                                                                                                                                                                                   
                                                                                                                                                                                                                                                              
//   Key behavior                                                                                                                                                                                                                                                
                                                                                                                                                                                                                                                              
//   // When logged-in user adds to cart:                                                                                                                                                                                                                        
                  
//   // 1. Cart saved to session (in-memory during request)                                                                                                                                                                                                      
//   WC()->session->set('cart', $cart_data);
                                                                                                                                                                                                                                                              
//   // 2. Also saved persistently to user meta
//   update_user_meta($user_id, '_woocommerce_persistent_cart_1', $cart_data);                                                                                                                                                                                   
                                                                                                                                                                                                                                                              
//   // This means: if the session expires, cart is STILL recoverable                                                                                                                                                                                            
//   // from user meta on next login                                                                                                                                                                                                                             
                                                                                                                                                                                                                                                              
//   What happens when logged-in user returns
                                                                                                                                                                                                                                                              
//   User logs in again (even on different device)                                                                                                                                                                                                               
//           │
//           ▼                                                                                                                                                                                                                                                   
//   WooCommerce checks: does _woocommerce_persistent_cart_1 exist?
//           │                                                                                                                                                                                                                                                   
//           ├── YES → Load cart from user meta → restore into session
//           │                                                                                                                                                                                                                                                   
//           └── NO  → Empty cart
                                                                                                                                                                                                                                                              
//   This is why logged-in users never "lose" their cart — it's tied to their account, not a browser cookie.                                                                                                                                                     
                                                                                                                                                                                                                                                              
//   ---                                                                                                                                                                                                                                                         
//   Guest User Sessions
                     
//   Storage: wp_woocommerce_sessions table
                                                                                                                                                                                                                                                              
//   wp_woocommerce_sessions
//   ├── session_id  | session_key                        | session_value            | session_expiry                                                                                                                                                            
//   ├── 1           | a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6   | {serialized session}     | 1714444800                                                                                                                                                                
//   ├── 2           | x9y8z7w6v5u4t3s2r1q0p9o8n7m6l5k4   | {serialized session}     | 1714444800                                                                                                                                                                
                                                                                                                                                                                                                                                              
//   Cookie                                                                                                                                                                                                                                                      
                                                                                                                                                                                                                                                              
//   Cookie name:  wp_woocommerce_session_<COOKIEHASH>                                                                                                                                                                                                           
//   Cookie value: guest_id||expiry||expiring||cookie_hash
                                                                                                                                                                                                                                                              
//   Example:
//   wp_woocommerce_session_abc123 = "a1b2c3d4...||1714444800||1714358400||hmac_hash"                                                                                                                                                                            
                                                                                                                                                                                                                                                              
//   How it works                                                                                                                                                                                                                                                
                                                                                                                                                                                                                                                              
//   Guest visits site                                                                                                                                                                                                                                           
//       │           
//       ▼
//   No cookie found → generate random 32-char ID
//       │                                                                                                                                                                                                                                                       
//       ▼
//   Guest adds product to cart                                                                                                                                                                                                                                  
//       │           
//       ▼
//   WooCommerce creates session:                                                                                                                                                                                                                                
//     1. Set cookie in browser (wp_woocommerce_session_xxx)
//     2. Save session data to wp_woocommerce_sessions table                                                                                                                                                                                                     
//       │                                                                                                                                                                                                                                                       
//       ▼                                                                                                                                                                                                                                                       
//   Guest browses more pages                                                                                                                                                                                                                                    
//       │           
//       ▼
//   Each page load:
//     1. Read cookie → get session_key
//     2. Load session from wp_woocommerce_sessions WHERE session_key = 'a1b2c3...'                                                                                                                                                                              
//     3. Cart, customer data, chosen shipping — all restored                                                                                                                                                                                                    
                                                                                                                                                                                                                                                              
//   ---                                                                                                                                                                                                                                                         
//   Session Expiry  
                                                                                                                                                                                                                                                              
//   Timing          

//   ┌──────────────────┬─────────────────────────────────────────────┬────────────────────────────────────┐                                                                                                                                                     
//   │    User Type     │              Session Duration               │          Can Be Extended?          │
//   ├──────────────────┼─────────────────────────────────────────────┼────────────────────────────────────┤                                                                                                                                                     
//   │ Logged-in        │ Never expires (persistent cart in usermeta) │ N/A — lives forever until cleared  │
//   ├──────────────────┼─────────────────────────────────────────────┼────────────────────────────────────┤
//   │ Guest cookie     │ 48 hours from creation                      │ Yes — extends on activity          │                                                                                                                                                     
//   ├──────────────────┼─────────────────────────────────────────────┼────────────────────────────────────┤                                                                                                                                                     
//   │ Guest DB session │ 48 hours                                    │ Extended to 48h from last activity