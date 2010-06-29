/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra.thrift;

import java.util.List;
import java.util.Map;
import java.util.Set;

import me.prettyprint.cassandra.service.CassandraClient;
import me.prettyprint.cassandra.service.CassandraClientPool;
import me.prettyprint.cassandra.service.CassandraClientPoolFactory;
import me.prettyprint.cassandra.service.PoolExhaustedException;

import org.apache.cassandra.thrift.Cassandra.Client;
import org.apache.thrift.TException;
import org.softao.jassandra.ConsistencyLevel;
import org.softao.jassandra.IConnection;
import org.softao.jassandra.IKeySpace;
import org.softao.jassandra.JassandraException;

import com.google.common.collect.ImmutableMap;
import com.google.common.collect.ImmutableMap.Builder;

/**
 * An implementation of the {@link IConnection}, which is used internally for
 * <code>Thrift</code> implementation.
 * <p>
 * This class is not thread-safe.
 */
class ThriftConnection implements IConnection {
	private boolean mIsClosed = false;
	private Map<String, IKeySpace> mKeySpaces;

	private final String mHost;
	private final int mPort;
	private ConsistencyLevel mConsistencyLevel = ConsistencyLevel.ONE;

	private CassandraClient mClient;
	private CassandraClientPool mClientPool;

	/**
	 * @throws JassandraException 
	 */
	ThriftConnection(List<String> hostPortUrls) throws JassandraException {
		if (hostPortUrls == null) {
			throw new NullPointerException("hostPortUrls");
		}
		String[] urls = hostPortUrls.toArray(new String[hostPortUrls.size()]);
		
		mClientPool = CassandraClientPoolFactory.INSTANCE.get();
		try {
			mClient = mClientPool.borrowClient(urls);
			
			// Potentially, the host name has been converted to IP
			mHost = mClient.getIp();
			mPort = mClient.getPort();
		} catch (IllegalStateException e) {
			throw new JassandraException("Fail to open connection.", e);
		} catch (PoolExhaustedException e) {
			throw new JassandraException("Fail to open connection.", e);
		} catch (Exception e) {
			throw new JassandraException("Fail to open connection.", e);
		}
	}
	
	/**
	 * Initializes a new instance of the <code>ThriftConnection</code> with host
	 * and port.
	 * 
	 * @param host
	 *            the host to the service
	 * @param port
	 *            the port to the service
	 * @throws JassandraException
	 */
	ThriftConnection(String host, int port) throws JassandraException {
		if (host == null) {
			throw new NullPointerException("host");
		}

		mHost = host;
		mPort = port;

		mClientPool = CassandraClientPoolFactory.INSTANCE.get();
		try {
			mClient = mClientPool.borrowClient(mHost, mPort);
		} catch (IllegalStateException e) {
			throw new JassandraException("Fail to open connection.", e);
		} catch (PoolExhaustedException e) {
			throw new JassandraException("Fail to open connection.", e);
		} catch (Exception e) {
			throw new JassandraException("Fail to open connection.", e);
		}
	}

	/**
	 * Since utilizing the feature from Hector, the close is actually returns
	 * the client to the pool.
	 */
	@Override
	public void close() throws JassandraException {
		try {
			mClientPool.releaseClient(mClient);
		} catch (Exception e) {
			throw new JassandraException("Fail to close connection.", e);
		}
		mIsClosed = true;
	}

	@Override
	public boolean isClosed() throws JassandraException {
		return mIsClosed;
	}

	@Override
	public IKeySpace getKeySpace(String name) throws JassandraException {
		ensureKeySpaces();

		if (mKeySpaces.containsKey(name)) {
			return mKeySpaces.get(name);
		}

		String msg = String.format("Cannot find keyspace %1$s.", name);
		throw new JassandraException(msg);
	}

	/**
	 * @return the client
	 */
	Client getClient() {
		return mClient.getCassandra();
	}

	/**
	 * @return the consistencyLevel
	 */
	public ConsistencyLevel getConsistencyLevel() {
		return mConsistencyLevel;
	}

	/**
	 * @param consistencyLevel
	 *            the consistencyLevel to set
	 */
	void setConsistencyLevel(ConsistencyLevel consistencyLevel) {
		mConsistencyLevel = consistencyLevel;
	}

	@Override
	public Map<String, IKeySpace> getKeySpaces() throws JassandraException {
		ensureKeySpaces();

		return mKeySpaces;
	}

	private void ensureKeySpaces() throws JassandraException {
		if (mKeySpaces != null) {
			return;
		}
		Builder<String, IKeySpace> builder = new ImmutableMap.Builder<String, IKeySpace>();
		try {
			Set<String> keyspaces = mClient.getCassandra().describe_keyspaces();
			for (String name : keyspaces) {
				ThriftKeySpace ks = new ThriftKeySpace(this, name);
				builder.put(name, ks);
			}
			mKeySpaces = builder.build();
		} catch (TException e) {
			throw new JassandraException("Access cassandra failed.", e);
		}
	}
}
