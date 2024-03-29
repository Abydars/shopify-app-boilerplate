<?php

unset( $_SESSION['session_id'] );

do_redirect( a_link( "/auth" ) );
