<?php

function sew_add_custom_user_profile_fields( $user ) {
	?>
    <h3>Slynk User Fields</h3>
    <table class="form-table">
        <tr>
            <th>
                Epos Now Loyalty Points Balance
            </th>
            <td>
	            <?php
                    $sew_customer_points = esc_attr( get_the_author_meta( 'slynk_eposnow_loyalty_points_balance', $user->ID ) );

                    if(empty($sew_customer_points)){
	                    $sew_customer_points = 0;
                    }

                    echo $sew_customer_points;

                ?> points
            </td>
        </tr>
    </table>

<?php }


add_action( 'show_user_profile', 'sew_add_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'sew_add_custom_user_profile_fields' );