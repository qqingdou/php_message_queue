# php_message_queue
PHP消息队列

PHP >=5.3

REDIS >= 3.2.0

# PHP扩展(php -m)
pcntl

posix

redis

sysvsem

# 该系统采用REDIS作为队列服务，请确保已经安装REDIS
/redis/you/path/redis-cli

# 消费
修改start.php中配置：

    /*******************配置从此开始*****************/
    
    /******进程数，建议为CPU核数******/
    $process_count = 2;
    /******redis配置****************/
    $redisConfig = array(
        'host' => '127.0.0.1',
        'port' => 6379,
    );
    
    /*******************配置从此结束*****************/

sudo php start.php [project_name]

e.g. 

    sudo php start.php gamecenter

# 生产
修改PhpMessageProduce.php中配置，然后在项目中引入 require "/you/path/PhpMessageProduce.php"或者自动加载：

    /**
     * REDIS主机地址
     * @var string
     */
    public $redisHost = '127.0.0.1';
    /**
     * REDIS端口
     * @var string
     */
    public $redisPort = '6379';
    
使用方式(默认只支持HTTP协议)：

    $phpMsgQueue = new PhpMessageProduce();
    $phpMsgQueue->push('gamecenter', array('url' => 'http://www.test.com', 'protocol' => 'http', 'post_data' => 'param1=123&param2=456'));

# 查看状态
sudo php service.php [project_name] status

e.g.

    sudo php service.php gamecenter status
    
# 停止项目
sudo php service.php [project_name] stop

e.g.

    sudo php service.php gamecenter stop