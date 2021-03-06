<?php
/*
Plugin Name: Wechat Robot
Plugin URI:  http://github.com/wangvsa/wechat-robot
Description: Wechat Robot的功能包括：0.访问站点 1.查看最新文章 2.查看随机文章 3.查看热门文章 4.关键字搜索
Version: 1.0
Author: WangChen
Author URI: http://wangchen.info
*/

define('WEIXIN_ROBOT_PLUGIN_URL', plugins_url('', __FILE__));
define('WEIXIN_ROBOT_PLUGIN_DIR', WP_PLUGIN_DIR.'/'. dirname(plugin_basename(__FILE__)));
define('WEIXIN_ROBOT_PLUGIN_FILE',  __FILE__);

include(WEIXIN_ROBOT_PLUGIN_DIR.'/functions.php');
require(WEIXIN_ROBOT_PLUGIN_DIR.'/wechat.php');

add_action('parse_request', 'wechat_robot_redirect', 4);
function wechat_robot_redirect( $wp ) {
	if( isset( $_GET['wechat'] ) ) {
        $robot = new WechatRobot("wechat", true);
        $robot->run();
    }
}


class WechatRobot extends Wechat {

    protected function queryAndResponse( $arg ) {

        $the_query = new WP_Query( $arg );
        if( $the_query->have_posts() ) {

            $counter = 0;
            $items = array();

            while( $the_query->have_posts() ) {
                $the_query->the_post();
                global $post;

                $title = get_the_title();
                $link = get_permalink();
                $excerpt = wechat_get_excerpt( get_the_excerpt() );

                if ( $counter == 0 ) {
                    $thumb = wechat_get_thumb($post, array(640, 320));
				} else {
                    $thumb = wechat_get_thumb($post, array(80, 80));
				}

                $new_item = new NewsResponseItem($title, $excerpt, $thumb, $link);
                array_push($items, $new_item);

                // 最多显示3篇
                if ( ++$counter == 3 )
                    break;
            }

            $this->responseNews($items);

        } else {
            $this->responseText("不好意思～没有找到您想要的东东～请换个关键字再试试？");
        }


        wp_reset_postdata();

    }

    protected function searchPosts( $key ) {
		$arg = array( 's' => $key );
        $this->queryAndResponse( $arg );
    }

    protected function recentPosts() {
		$arg = array( 'ignore_sticky_posts'=>1, 'showposts' => 3 );
        $this->queryAndResponse( $arg );
    }

    protected function randomPosts() {
        $arg = array ( 'ignore_sticky_posts' => 1, 'orderby' => 'rand' );
        $this->queryAndResponse( $arg );
    }

    protected function hotestPosts() {      // 本月最热门
        $arg = array (
            'ignore_sticky_posts' => 1,
            'orderby' => 'comment_count',
            'year' => date('Y'),
            'monthnum' => date('m')
        );
        $this->queryAndResponse( $arg );
    }


    protected function onText() {
        $msg = $this->getRequest('content');
        if( $msg == '0' ) {
            $this->responseText("访问FreeBuf黑客与极客,请点击下面连接:\n http://www.freebuf.com ");
        } else if( $msg == '1' ) {
            $this->recentPosts();
        } else if ( $msg == '2' ) {
            $this->randomPosts();
        } else if ( $msg == '3' ) {
            $this->hotestPosts();
        } else if ( strncmp($msg, '4', 1)==0 ) {    // starts with '4'
            if( strlen($msg) == 1 ) {
                $this->responseText("您没有输入关键字，要输入[4关键字]进行搜索哦，比如 4黑客 ");
            } else {
                $this->searchPosts( substr($msg, 1, strlen($msg)-1) );
            }
        } else {
            $this->responseText("欢迎关注FreeBuf黑客与极客\n 回复[0]访问FreeBuf\n 回复[1]查看最新文章".
                    "\n 回复[2]查看随机文章\n 回复[3]查看热门文章\n 回复[4关键字]搜索文章\n 回复其他内容查看本菜单");
        }
    }

    protected function onSubscribe() {
        $this->responseText("欢迎关注FreeBuf黑客与极客\n 回复[0]访问FreeBuf\n 回复[1]查看最新文章".
                "\n 回复[2]查看随机文章\n 回复[3]查看热门文章\n 回复[4关键字]搜索文章\n 回复其他内容查看本菜单");
    }


}
