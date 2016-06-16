var assert = require('chai').assert
var util = require('util')
var fs = require('fs')
var Oauth = require('oauth').OAuth
var request = require('superagent')
var env = require('./env.json') 
var facebook = require('./facebook.json')

var base = env.site.url
var namespace = env.site.namespace

require('superagent-oauth')(request)

var oauth = new Oauth(null,null,
	env.keys.consumer.key,
	env.keys.consumer.secret,
	'1.0A',
	null,
	'HMAC-SHA1'
)

describe('OAuth1.0', function(){
	it('testear el proceso de autorizacion de un usuario oauth royale/v1')
})

describe('/auth', function(){
	describe('/login', function(){
		this.timeout(5000);
		it('POST: deberia devolver info. del usuario', function(done){
			var body = {
				'access_token' : facebook.accessToken
			}
			request.post(base + namespace +'/auth/login')
			.sign(oauth, env.keys.oauth.token, env.keys.oauth.secret)
			.send(body)
			.end(function(err, res){
				//status
				if (err) console.log('    ',err);

				// console.log('    ',res.text)
				assert.property(res, 'status')
				assert.isAtLeast(res.status, 200)
				assert.isAtMost(res.status, 201)
				
				//headers
				if('application/json; charset=UTF-8' !=	res.headers['content-type']) assert.isNotOk('headers-error', res.text); 

				//body
				assert.notProperty(res.body, 'error')
				assert.notProperty(res.body, 'code')

				assert.typeOf(res.body.user.user_login, 'string', 'user_login')
				assert.typeOf(res.body.user.display_name, 'string', 'display_name')
				assert.typeOf(res.body.user.role, 'string', 'role')
				assert.typeOf(res.body.user.user_email, 'string', 'user_email')
				assert.isNumber(res.body.user.ID, 'ID')
				assert.typeOf(res.body.token, 'string', 'token')

				var wenv = env
				wenv.keys.user.access_token = res.body.token

				fs.writeFile('./env.json', JSON.stringify(wenv), function (err) {
				  	if (err) return console.log(err);
				  	// console.log('      - escrito el archivo con el nuevo token');
					done()	
				});
			})
		})

		it('DELETE: deberia eliminar la session del usuario', function(done){
			var env = require('./env.json')
			var body = {
				'token' : env.keys.user.access_token
			}
			request.delete(base + namespace +'/auth/login')
			.sign(oauth, env.keys.oauth.token, env.keys.oauth.secret)
			.send(body)
			.end(function(err, res){
				//status
				// if (err) console.error(err)
				// console.log(res.text)
				assert.property(res, 'status')
				assert.equal(res.status, 200)
				
				// //headers
				if('application/json; charset=UTF-8' !=	res.headers['content-type']) assert.isNotOk('headers-error', res.text); 

				// //body
				assert.notProperty(res.body, 'error')
				assert.notProperty(res.body, 'code')

				assert.isBoolean(res.body.success, 'success')
				assert.equal(res.body.success, true, 'success')

					request.delete(base + namespace +'/auth/login')
					.sign(oauth, env.keys.oauth.token, env.keys.oauth.secret)
					.send(body)
					.end(function(err, res){
						// console.log('      ', res.status, res.text)
						assert.property(res, 'status')
						assert.equal(res.status, 403)
						done()	
					})
			})
		})
	})
})