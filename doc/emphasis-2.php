<?php
/**
 * #test
 * <code>
 * #is(emphasis("great"),"great!!","add !! emphasised.");
 * </code>
 *
 */
function emphasis($word){
    return $word."!!";	 
}
