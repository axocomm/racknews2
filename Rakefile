require 'yaml'

CONFIG = 'resources/config.yaml'
SERVICE_BASE = File.basename(Dir.pwd).gsub(/[^A-Za-z0-9]/, '')

class Hash
  def symbolize
    self.inject({}) do |acc, (k, v)|
      v = v.is_a?(Hash) ? v.symbolize : v
      acc[k.to_sym] = v
      acc
    end
  end
end

def load_config(filename)
  YAML.load(File.read(filename)).symbolize
end

# Generate exports for running docker-compose
def export_str(config)
  'export ' + config.map do |k, v|
    "#{k.upcase}=#{v}"
  end.join(' ')
end

begin
  $config = load_config CONFIG
rescue Exception => e
  puts "Could not get configuration: #{e.message}"
  exit 1
end

desc 'Build images'
task :build, [:services] do |_, args|
  s_str = (args[:services] || '').gsub(/:/, ' ')
  sh "#{export_str($config)} && docker-compose build #{s_str}"
end

desc 'Start containers'
task :start do
  d_str = ENV['DETACH'] ? '-d' : ''
  sh "#{export_str($config)} && docker-compose up #{d_str}"
end

desc 'Stop containers'
task :down do
  sh 'docker-compose down'
end

desc 'Run a shell in container'
task :enter, [:service] do |_, args|
  name = "#{SERVICE_BASE}_#{args[:service]}_1"
  sh "docker exec -it #{name} bash"
end

desc 'Run database shell'
task :dbshell do
  cmd = <<-EOT
    docker run \
      -it \
      --link #{SERVICE_BASE}_db_1:mariadb \
      --rm \
      --net #{SERVICE_BASE}_default \
      mariadb \
      sh -c 'exec mysql -hdb -uroot -psecretlol'
  EOT

  sh cmd
end
