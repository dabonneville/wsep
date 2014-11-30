# -*- coding: utf-8 -*-

# Scrapy settings for bird project
#
# For simplicity, this file contains only the most important settings by
# default. All the other settings are documented here:
#
#     http://doc.scrapy.org/en/latest/topics/settings.html
#

COOKIES_ENABLED = False 
BOT_NAME = 'bird'
SPIDER_MODULES = ['bird.spiders']
NEWSPIDER_MODULE = 'bird.spiders'
ITEM_PIPELINES = {
    'bird.pipelines.BirdPipeline':300
}
#取消默认的useragent,使用新的useragent
DOWNLOADER_MIDDLEWARES = {
        'scrapy.contrib.downloadermiddleware.useragent.UserAgentMiddleware' : None,
        'bird.rotate_useragent.RotateUserAgentMiddleware' :400
    }
# Crawl responsibly by identifying yourself (and your website) on the user-agent
#USER_AGENT = 'bird (+http://www.yourdomain.com)'
