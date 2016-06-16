const http = require('http')
const fs = require('fs')
const hostname = '127.0.0.20'
const port = 3000

const server = http.createServer()

server.on('request', (req, res) => {
	if (req.url === '/') {
		fs.readFile(__dirname + '/index.html', (err, data) => {
			if(err) console.error(err)
			res.statusCode = 200
			res.setHeader('Content-Type', 'text/html')
			res.write(data)
			res.end()
		})
	}else if(req.url === '/json' && req.method === 'POST' ){
	 	var body = {}
		console.log('/json POST')
		req.setEncoding('utf8')
		req.on('data', function (json) {
		    body = JSON.parse(json)
		}).on('end', () => {
			if(typeof(body.accessToken) == 'string'){
				fs.writeFile(__dirname + '/../facebook.json', JSON.stringify(body), (err) => {
					if(err) throw err;
					res.statusCode = 200
					res.setHeader('Content-Type', 'application/json')
					res.write(JSON.stringify({'success':true}))
					res.end()
				})
			} else {
				res.statusCode = 400
				res.setHeader('Content-Type', 'application/json')
				res.write(JSON.stringify({'error':'accessToken_not_found'}))
				res.end()
			}
		})

	}else{
		res.statusCode = 404
		res.setHeader('Content-Type', 'text/plain')
		res.end('Not found\n')
	}
})

server.listen(port, hostname, () => {
	console.log('Server running at http://' + hostname + ':' + port + '/')
})