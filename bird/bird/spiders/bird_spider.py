# from scrapy import log
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from bird.items import BirdItem
from scrapy.http import Request
import time
# from scrapy import log
# from bird.pipelines import *
flag=7
ISOTIMEFORMAT='%Y-%m-%dT%X'
class BirdSpider(BaseSpider):
	name ="bird.org"
	download_delay = 2
	# allowed_domains=["douban.com"]
	start_urls = [
	# "http://www.douban.com/event/search?search_text=%E4%BA%92%E8%81%94%E7%BD%91&loc=china",
	# "http://www.douban.com/event/search?search_text=IT&loc=china"
	# "http://www.douban.com/event/search?search_text=%E5%AE%B6%E5%BA%AD&loc=china",
	# "http://www.douban.com/event/search?search_text=%E4%BA%B2%E5%AD%90&loc=china"
	# "http://www.douban.com/event/search?search_text=%E6%B8%B8%E5%9B%AD&loc=china",
	# "http://www.douban.com/event/search?search_text=%E9%9B%86%E5%B8%82&loc=china"
	# "http://www.douban.com/event/search?search_text=%E6%96%87%E8%89%BA&loc=china",
	"http://www.douban.com/event/search?search_text=%E6%BC%94%E5%87%BA&loc=china"
	# "http://www.douban.com/event/search?search_text=%E4%BD%93%E8%82%B2&loc=china",
	# "http://www.douban.com/event/search?search_text=%E8%B5%9B%E4%BA%8B&loc=china",
	# "http://www.douban.com/event/search?search_text=%E7%94%B5%E5%BD%B1&loc=china",
	# "http://www.douban.com/event/search?search_text=%E7%94%B5%E8%A7%86&loc=china",
	# "http://www.douban.com/event/search?search_text=%E5%8D%9A%E7%89%A9&loc=china",
	# "http://www.douban.com/event/search?search_text=%E5%8D%9A%E8%A7%88&loc=china"
	# "http://www.douban.com/event/search?search_text=%E7%95%99%E5%AD%A6&loc=china",
	# "http://www.douban.com/event/search?search_text=%E7%A7%BB%E6%B0%91&loc=china"
	]
	def parse(self,response):
		global flag
		hxs=HtmlXPathSelector(response)
		flag +=1
		sites= hxs.select('//li[@class="search-list-entry"]')
		items =[]
		for site in sites:
			link=site.select('div[@class="info"]/div[@class="event-title"]/a/@href').extract().pop()
			# items.append(Request(link,callback=self.parse_info))
			information=Request(link,callback=self.parse_info)
			if information !=None:
				items.append(information)
		pages = hxs.select('//div[@class="paginator"]')
		validurl =[]
		page_id = int(pages.select('span[@class="thispage"]/@data-total-page').extract().pop())
		for i in range(1,page_id):
			id_temp=i*12;
			validurl.append(response.url+"&start="+str(id_temp))
		items.extend([Request(url,callback=self.parse_address) for url in validurl])
		return items
	def parse_address(self,response):
		hxs=HtmlXPathSelector(response)
		sites= hxs.select('//li[@class="search-list-entry"]')
		items =[]
		for site in sites:
			link=site.select('div[@class="info"]/div[@class="event-title"]/a/@href').extract().pop()
			items.append(Request(link,callback=self.parse_info))
		return items
	def parse_info(self,response):
		global flag
		global ISOTIMEFORMAT
		now = time.strftime(ISOTIMEFORMAT,time.localtime(time.time()+24*60*60))
		hxs = HtmlXPathSelector(response)
		item = BirdItem()
		item['endDate']=hxs.select('//time[@itemprop="endDate"]/@datetime').extract().pop()
		if item['endDate'] < now :
			return
		item['latitude']=hxs.select('//span[@itemprop="geo"]/meta[@itemprop="latitude"]/@content').extract().pop()
		item['longitude']=hxs.select('//span[@itemprop="geo"]/meta[@itemprop="longitude"]/@content').extract().pop()
		item['startDate']=hxs.select('//time[@itemprop="startDate"]/@datetime').extract().pop()
		if item['startDate'] <=now :
			item['today'] =1
		else :
			item['today'] =0
		item['url']=response.url
		item['imgSrc'] = hxs.select('//img[@id="poster_img"]/@src').extract().pop()
		item['title'] = hxs.select('//meta[@name="twitter:title"]/@content').extract().pop()
		times = hxs.select('//li[@class="calendar-str-item "]/text()').extract().pop()
		item['time'] = times
		addresses = hxs.select('//span[@itemprop="address"]/span')
		item['city'] = addresses[0].select('text()').extract().pop().strip()
		item['area'] = addresses[1].select('text()').extract().pop().strip()
		a=response.url.split('/')
		item['eventid'] = a[len(a)-2]
		item['sort'] = str(flag/2)
		location = ""
		for address in addresses:
			location += address.select('text()').extract().pop()
		item['address'] = location
		prices = hxs.select('//div[@class="event-detail"]')
		item['price'] =prices[2].select('text()').extract()[1]
		item['category'] =prices[3].select('a/text()').extract()[0]
		info = hxs.select('//div[@style="display:none"]/text()').extract()
		str_endl="<br/>"
		if info :
			infos = info
			# item['info'] = "0"
			item['info'] = str_endl.join(infos)
		else :
			infos = hxs.select('//div[@class="wr"]/text()').extract()
			# item['info'] ="1"
			item['info'] = str_endl.join(infos)
		return item






