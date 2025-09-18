<?php

////////////////////////////////////////////////////////////////////////////
// It's common to have a global header/footer and include content in the  //
// middle. This can be accomplished with a parent template. When a parent //
// template is specified $__CHILD_TPL is populated with the original      //
// template filename. This can then be {include file="$__CHILD_TPL"} in   //
// the parent.                                                            //
////////////////////////////////////////////////////////////////////////////

require('../sluz.class.php');
$s = new sluz();

// Two parameter fetch()
print $s->fetch("tpls/070_child.stpl", "tpls/070_parent.stpl");

// You can also set a parent template and then use normal fetch()
//$s->parent_tpl("tpls/070_parent.stpl");
//print $s->fetch("tpls/070_child.stpl");
